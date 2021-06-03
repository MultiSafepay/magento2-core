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
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction as PaymentTransaction;
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
use MultiSafepay\ConnectCore\Util\GiftcardUtil;
use MultiSafepay\ConnectCore\Util\JsonHandler;
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
     * @var OrderPaymentRepositoryInterface
     */
    private $orderPaymentRepository;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var OrderStatusUtil
     */
    private $orderStatusUtil;

    /**
     * @var UpdateRequest
     */
    private $updateRequest;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SdkFactory
     */
    private $sdkFactory;

    /**
     * @var InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    /**
     * @var JsonHandler
     */
    private $jsonHandler;

    /**
     * @var GiftcardUtil
     */
    private $giftcardUtil;

    /**
     * OrderService constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param EmailSender $emailSender
     * @param Vault $vault
     * @param PaymentMethodUtil $paymentMethodUtil
     * @param SecondChance $secondChance
     * @param Logger $logger
     * @param OrderPaymentRepositoryInterface $orderPaymentRepository
     * @param TransactionRepositoryInterface $transactionRepository
     * @param UpdateRequest $updateRequest
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param Config $config
     * @param SdkFactory $sdkFactory
     * @param OrderStatusUtil $orderStatusUtil
     * @param JsonHandler $jsonHandler
     * @param GiftcardUtil $giftcardUtil
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        EmailSender $emailSender,
        Vault $vault,
        PaymentMethodUtil $paymentMethodUtil,
        SecondChance $secondChance,
        Logger $logger,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        TransactionRepositoryInterface $transactionRepository,
        UpdateRequest $updateRequest,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        InvoiceRepositoryInterface $invoiceRepository,
        Config $config,
        SdkFactory $sdkFactory,
        OrderStatusUtil $orderStatusUtil,
        JsonHandler $jsonHandler,
        GiftcardUtil $giftcardUtil
    ) {
        $this->orderRepository = $orderRepository;
        $this->emailSender = $emailSender;
        $this->vault = $vault;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->secondChance = $secondChance;
        $this->logger = $logger;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->transactionRepository = $transactionRepository;
        $this->updateRequest = $updateRequest;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->invoiceRepository = $invoiceRepository;
        $this->config = $config;
        $this->sdkFactory = $sdkFactory;
        $this->orderStatusUtil = $orderStatusUtil;
        $this->jsonHandler = $jsonHandler;
        $this->giftcardUtil = $giftcardUtil;
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
        $transactionData = $transaction->getData();
        unset($transactionData['payment_details'], $transactionData['payment_methods']);

        $this->logger->logInfoForOrder(
            $orderId,
            __(
                'Transaction data was retrieved: %1',
                $this->jsonHandler->convertToPrettyJSON($transactionData)
            )->render(),
            Logger::DEBUG
        );

        $transactionStatus = $transaction->getStatus();

        if (in_array($transactionStatus, [
                TransactionStatus::COMPLETED,
                TransactionStatus::INITIALIZED,
                TransactionStatus::RESERVED,
                TransactionStatus::SHIPPED,
            ], true)
            && $this->emailSender->sendOrderConfirmationEmail($order)) {
            $this->logger->logInfoForOrder(
                $orderId,
                __('Order confirmation email after transaction has been sent')->render(),
                Logger::DEBUG
            );
        }

        /** @var Payment $payment */
        if (!($payment = $order->getPayment())) {
            throw new LocalizedException(__('Can\'t get the payment from Order.'));
        }

        $paymentDetails = $transaction->getPaymentDetails();
        $transactionType = $paymentDetails->getType();
        $gatewayCode = (string)$payment->getMethodInstance()->getConfigData('gateway_code');

        //Check if Vault needs to be initialized
        $isVaultInitialized = $this->vault->initialize($payment, [
            RecurringDetailsInterface::RECURRING_ID => $paymentDetails->getRecurringId(),
            RecurringDetailsInterface::TYPE => $transactionType,
            RecurringDetailsInterface::EXPIRATION_DATE => $paymentDetails->getCardExpiryDate(),
            RecurringDetailsInterface::CARD_LAST4 => $paymentDetails->getLast4(),
        ]);

        if ($isVaultInitialized) {
            $this->logger->logInfoForOrder($orderId, __('Vault has been initialized.')->render(), Logger::DEBUG);
        }

        if ($this->canChangePaymentMethod($transactionType, $gatewayCode, $order)) {
            if ($this->giftcardUtil->isFullGiftcardTransaction($transaction)) {
                $transactionType = $this->giftcardUtil->getGiftcardGatewayCodeFromTransaction($transaction) ?:
                    $transactionType;
            }

            $this->changePaymentMethod($order, $payment, $transactionType);
        }

        $transactionStatusMessage = __('MultiSafepay Transaction status: ') . $transactionStatus;
        $order->addCommentToStatusHistory($transactionStatusMessage);
        $this->logger->logInfoForOrder($orderId, $transactionStatusMessage, Logger::DEBUG);

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
                if (in_array($order->getState(), [Order::STATE_PENDING_PAYMENT, Order::STATE_NEW], true)) {
                    $this->cancelOrder($order, $transactionStatusMessage);
                }
                break;
            case TransactionStatus::DECLINED:
            case TransactionStatus::CANCELLED:
            case TransactionStatus::VOID:
                $this->cancelOrder($order, $transactionStatusMessage);
                break;
        }

        if ($giftcardData = $this->giftcardUtil->getGiftcardPaymentDataFromTransaction($transaction)) {
            $payment->setAdditionalInformation(
                GiftcardUtil::MULTISAFEPAY_GIFTCARD_PAYMENT_ADDITIONAL_DATA_PARAM_NAME,
                $giftcardData
            );
        }

        $this->orderRepository->save($order);
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
                $this->logger->logInfoForOrder($order->getIncrementId(), $logMessage, Logger::DEBUG);

                return;
            }
        }
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
        $this->logger->logInfoForOrder(
            $orderId,
            __('MultiSafepay order transaction complete process has been started.')->render(),
            Logger::DEBUG
        );

        if ($order->getState() === Order::STATE_CANCELED) {
            $this->secondChance->reopenOrder($order);
            $this->logger->logInfoForOrder($orderId, __('The order has been reopened.')->render(), Logger::DEBUG);
        }

        if ($this->emailSender->sendOrderConfirmationEmail(
            $order,
            EmailSender::AFTER_PAID_TRANSACTION_EMAIL_TYPE
        )) {
            $this->logger->logInfoForOrder(
                $orderId,
                __('Order confirmation email after paid transaction has been sent')->render(),
                Logger::DEBUG
            );
        }

        $this->payOrder($order, $payment, $transaction);
        $this->addInvoicesDataToTransactionAndSendEmail($order, $payment, $transactionManager);

        $this->logger->logInfoForOrder(
            $orderId,
            __('MultiSafepay order transaction complete process has been finished successfully.')->render(),
            Logger::DEBUG
        );
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param Transaction $transaction
     * @throws LocalizedException
     */
    private function payOrder(
        OrderInterface $order,
        OrderPaymentInterface $payment,
        Transaction $transaction
    ): void {
        if ($order->canInvoice()) {
            $orderId = $order->getIncrementId();
            $payment->setTransactionId($transaction->getData()['transaction_id'])
                ->setAdditionalInformation(
                    [PaymentTransaction::RAW_DETAILS => (array)$payment->getAdditionalInformation()]
                )->setShouldCloseParentTransaction(false)
                ->setIsTransactionClosed(0)
                ->registerCaptureNotification($order->getBaseTotalDue(), true);

            $payment->setParentTransactionId($transaction->getData()['transaction_id']);
            $payment->setIsTransactionApproved(true);
            $this->orderPaymentRepository->save($payment);
            $this->logger->logInfoForOrder($orderId, 'Invoice created', Logger::DEBUG);
            $paymentTransaction = $payment->addTransaction(
                PaymentTransaction::TYPE_CAPTURE,
                null,
                true
            );

            if ($paymentTransaction !== null) {
                $paymentTransaction->setParentTxnId($transaction->getData()['transaction_id']);
            }

            $paymentTransaction->setIsClosed(1);
            $this->transactionRepository->save($paymentTransaction);

            // Set order processing
            $status = $this->orderStatusUtil->getProcessingStatus($order);
            $order->setState(Order::STATE_PROCESSING);
            $order->setStatus($status);
            $this->orderRepository->save($order);
            $this->logger->logInfoForOrder(
                $orderId,
                'Order status has been changed to: ' . $status,
                Logger::DEBUG
            );
        }
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param TransactionManager $transactionManager
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    private function addInvoicesDataToTransactionAndSendEmail(
        OrderInterface $order,
        OrderPaymentInterface $payment,
        TransactionManager $transactionManager
    ): void {
        $orderId = $order->getIncrementId();

        foreach ($this->getInvoicesByOrderId($order->getId()) as $invoice) {
            $invoiceIncrementId = $invoice->getIncrementId();

            try {
                if ($this->emailSender->sendInvoiceEmail($payment, $invoice)) {
                    $this->logger->logInfoForOrder($orderId, __('Invoice email was sent.')->render(), Logger::DEBUG);
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
    }

    /**
     * @param string $orderId
     * @return InvoiceInterface[]
     */
    private function getInvoicesByOrderId(string $orderId): array
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('order_id', $orderId)->create();

        return $this->invoiceRepository->getList($searchCriteria)->getItems();
    }

    /**
     * @param OrderInterface $order
     * @param string $transactionStatusMessage
     */
    private function cancelOrder(OrderInterface $order, string $transactionStatusMessage): void
    {
        $order->cancel();
        $order->addCommentToStatusHistory($transactionStatusMessage);
        $this->logger->logInfoForOrder(
            $order->getIncrementId(),
            'Order has been canceled. ' . $transactionStatusMessage
        );
    }
}
