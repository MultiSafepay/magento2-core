<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * Copyright © 2021 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Service;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\Api\TransactionManager;
use MultiSafepay\Api\Transactions\Transaction as TransactionStatus;
use MultiSafepay\Api\Transactions\TransactionResponse as Transaction;
use MultiSafepay\Api\Transactions\UpdateRequest;
use MultiSafepay\ConnectCore\Api\RecurringDetailsInterface;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\SecondChance;
use MultiSafepay\ConnectCore\Model\Vault;
use MultiSafepay\ConnectCore\Util\OrderStatusUtil;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;
use MultiSafepay\Exception\ApiException;
use Psr\Http\Client\ClientExceptionInterface;

class OrderService
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var EmailSender
     */
    private $emailSender;

    /**
     * @var Vault
     */
    private $vault;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var SecondChance
     */
    private $secondChance;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var OrderStatusUtil
     */
    private $orderStatusUtil;

    /**
     * @var UpdateRequest
     */
    private $updateRequest;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SdkFactory
     */
    private $sdkFactory;

    /**
     * @var InvoiceService
     */
    private $invoiceService;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        EmailSender $emailSender,
        Vault $vault,
        PaymentMethodUtil $paymentMethodUtil,
        SecondChance $secondChance,
        Logger $logger,
        UpdateRequest $updateRequest,
        Config $config,
        SdkFactory $sdkFactory,
        OrderStatusUtil $orderStatusUtil,
        InvoiceService $invoiceService
    ) {
        $this->orderRepository = $orderRepository;
        $this->emailSender = $emailSender;
        $this->vault = $vault;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->secondChance = $secondChance;
        $this->logger = $logger;
        $this->updateRequest = $updateRequest;
        $this->config = $config;
        $this->sdkFactory = $sdkFactory;
        $this->orderStatusUtil = $orderStatusUtil;
        $this->invoiceService = $invoiceService;
    }

    /**
     * @param OrderInterface $order
     * @throws ClientExceptionInterface
     * @throws LocalizedException
     * @throws Exception
     */
    public function processOrderTransaction(OrderInterface $order): void
    {
        $orderId = $order->getIncrementId();
        $transactionManager = $this->sdkFactory->create((int)$order->getStoreId())->getTransactionManager();
        $transaction = $transactionManager->get($orderId);

        if ($this->emailSender->sendOrderConfirmationEmail($order)) {
            $this->logger->logInfoForOrder(
                $orderId,
                __('Order confirmation email after transaction has been sent')->render()
            );
        }

        /** @var Payment $payment */
        if (!($payment = $order->getPayment())) {
            throw new LocalizedException(__('Can\'t get the payment from Order.'));
        }

        $paymentDetails = $transaction->getPaymentDetails();
        $transactionType = $paymentDetails->getType();
        $gatewayCode = $payment->getMethodInstance()->getConfigData('gateway_code');

        //Check if Vault needs to be initialized
        $isVaultInitialized = $this->vault->initialize($payment, [
            RecurringDetailsInterface::RECURRING_ID => $paymentDetails->getRecurringId(),
            RecurringDetailsInterface::TYPE => $transactionType,
            RecurringDetailsInterface::EXPIRATION_DATE => $paymentDetails->getCardExpiryDate(),
            RecurringDetailsInterface::CARD_LAST4 => $paymentDetails->getLast4(),
        ]);

        if ($isVaultInitialized) {
            $this->logger->logInfoForOrder($orderId, __('Vault has been initialized.')->render());
        }

        if ($this->canChangePaymentMethod($transactionType, $gatewayCode, $order)) {
            $this->changePaymentMethod($order, $payment, $transactionType);
        }

        $transactionStatus = $transaction->getStatus();
        $financialStatus = $transaction->getFinancialStatus();
        $order->addCommentToStatusHistory(__('MultiSafepay Transaction status: ') . $financialStatus);

        $transactionStatusMessage = __('MultiSafepay Transaction status: ') . $transactionStatus;
        $order->addCommentToStatusHistory($transactionStatusMessage);
        $this->logger->logInfoForOrder($orderId, $transactionStatusMessage);

        switch ($transactionStatus) {
            case TransactionStatus::COMPLETED:
            case TransactionStatus::SHIPPED:
                $this->completeOrderTransaction($order, $payment, $transaction, $transactionManager);
                break;

            case TransactionStatus::UNCLEARED:
                if ($gatewayCode !== 'SANTANDER') {
                    $msg = __('Uncleared Transaction. You can accept the transaction manually in MultiSafepay Control');
                    $order->addCommentToStatusHistory($msg);
                    $this->logger->logInfoForOrder($orderId, $msg->render());
                }
                break;

            case TransactionStatus::EXPIRED:
            case TransactionStatus::DECLINED:
            case TransactionStatus::CANCELLED:
            case TransactionStatus::VOID:
                $order->cancel();
                $order->addCommentToStatusHistory($transactionStatusMessage);
                $this->logger->logInfoForOrder(
                    $orderId,
                    'Order has been canceled. ' . $transactionStatusMessage
                );
                break;
        }

        $this->orderRepository->save($order);
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param string $transactionType
     */
    private function changePaymentMethod(
        OrderInterface $order,
        OrderPaymentInterface $payment,
        string $transactionType
    ): void {
        $methodList = $this->config->getValueByPath('payment');

        foreach ($methodList as $code => $method) {
            if (isset($method['gateway_code']) && $method['gateway_code'] === $transactionType
                && strpos($code, '_recurring') === false) {
                $payment->setMethod($code);

                $logMessage = __('Payment method changed to ') . $transactionType;

                $order->addCommentToStatusHistory($logMessage);
                $this->logger->logInfoForOrder($order->getIncrementId(), $logMessage);
            }
        }
    }

    /**
     * @param string $transactionType
     * @param string $gatewayCode
     * @param OrderInterface $order
     * @return bool
     */
    private function canChangePaymentMethod(string $transactionType, string $gatewayCode, OrderInterface $order): bool
    {
        return $transactionType && $transactionType !== $gatewayCode
               && $this->paymentMethodUtil->isMultisafepayOrder($order);
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param Transaction $transaction
     * @param TransactionManager $transactionManager
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    private function completeOrderTransaction(
        OrderInterface $order,
        OrderPaymentInterface $payment,
        Transaction $transaction,
        TransactionManager $transactionManager
    ): void {
        $orderId = $order->getIncrementId();
        $paymentDetails = $transaction->getPaymentDetails();
        $financialStatus = $transaction->getFinancialStatus();
        $this->logger->logInfoForOrder(
            $orderId,
            __('MultiSafepay order transaction complete process has been started.')->render()
        );

        if ($order->getState() === Order::STATE_CANCELED) {
            $this->secondChance->reopenOrder($order);
            $this->logger->logInfoForOrder($order->getIncrementId(), __('The order has been reopened.')->render());
        }

        if ($this->emailSender->sendOrderConfirmationEmail(
            $order,
            EmailSender::AFTER_PAID_TRANSACTION_EMAIL_TYPE
        )) {
            $this->logger->logInfoForOrder(
                $orderId,
                __('Order confirmation email after paid transaction has been sent')->render()
            );
        }

        if ($order->canInvoice()) {
            if (!($financialStatus === TransactionStatus::INITIALIZED
                && $paymentDetails->getCapture() === 'manual' && $paymentDetails->getCaptureRemain())
            ) {
                $this->invoiceService->invoiceByAmount($order, $payment, $transaction, $order->getBaseTotalDue());
            }

            // Set order processing
            $status = $this->orderStatusUtil->getProcessingStatus($order);
            $order->setState(Order::STATE_PROCESSING);
            $order->setStatus($status);
            $this->orderRepository->save($order);
            $this->logger->logInfoForOrder($orderId, 'Order status has been changed to: ' . $status);
        }

        foreach ($this->invoiceService->getInvoicesByOrderId($order->getId()) as $invoice) {
            $invoiceIncrementId = $invoice->getIncrementId();

            try {
                if ($this->emailSender->sendInvoiceEmail($payment, $invoice)) {
                    $this->logger->logInfoForOrder($orderId, __('Invoice email was sent.')->render());
                }
            } catch (MailException $mailException) {
                $this->logger->logExceptionForOrder($orderId, $mailException, Logger::INFO);
            }

            $updateRequest = $this->updateRequest->addData([
                "invoice_id" => $invoiceIncrementId,
            ]);

            try {
                $transactionManager->update($orderId, $updateRequest)->getResponseData();
                $this->logger->logInfoForOrder(
                    $orderId,
                    'Invoice: ' . $invoiceIncrementId . ' update request has been sent to MultiSafepay.'
                );
            } catch (ApiException $e) {
                $this->logger->logUpdateRequestApiException($orderId, $e);
            }
        }

        $this->logger->logInfoForOrder(
            $orderId,
            __('MultiSafepay order transaction complete process has been finished successfully.')->render()
        );
    }
}
