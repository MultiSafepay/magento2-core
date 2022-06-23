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
 * Copyright © 2022 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Service\Order;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use MultiSafepay\Api\TransactionManager;
use MultiSafepay\Api\Transactions\Transaction as TransactionStatus;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\SecondChance;
use MultiSafepay\ConnectCore\Service\EmailSender;
use MultiSafepay\ConnectCore\Util\OrderStatusUtil;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProcessOrderByTransactionStatus
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SecondChance
     */
    private $secondChance;

    /**
     * @var EmailSender
     */
    private $emailSender;

    /**
     * @var PayMultisafepayOrder
     */
    private $payMultisafepayOrder;

    /**
     * @var AddInvoicesDataToTransactionAndSendEmail
     */
    private $addInvoicesDataToTransactionAndSendEmail;

    /**
     * @var OrderStatusUtil
     */
    private $orderStatusUtil;

    /**
     * ProcessOrderByTransactionStatus constructor.
     *
     * @param Logger $logger
     * @param SecondChance $secondChance
     * @param EmailSender $emailSender
     * @param PayMultisafepayOrder $payMultisafepayOrder
     * @param AddInvoicesDataToTransactionAndSendEmail $addInvoicesDataToTransactionAndSendEmail
     * @param OrderStatusUtil $orderStatusUtil
     */
    public function __construct(
        Logger $logger,
        SecondChance $secondChance,
        EmailSender $emailSender,
        PayMultisafepayOrder $payMultisafepayOrder,
        AddInvoicesDataToTransactionAndSendEmail $addInvoicesDataToTransactionAndSendEmail,
        OrderStatusUtil $orderStatusUtil
    ) {
        $this->logger = $logger;
        $this->secondChance = $secondChance;
        $this->emailSender = $emailSender;
        $this->payMultisafepayOrder = $payMultisafepayOrder;
        $this->addInvoicesDataToTransactionAndSendEmail = $addInvoicesDataToTransactionAndSendEmail;
        $this->orderStatusUtil = $orderStatusUtil;
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param TransactionManager $transactionManager
     * @param array $transaction
     * @param string $transactionStatus
     * @param string $gatewayCode
     * @throws ClientExceptionInterface
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute(
        OrderInterface $order,
        OrderPaymentInterface $payment,
        TransactionManager $transactionManager,
        array $transaction,
        string $transactionStatus,
        string $gatewayCode
    ): void {
        $orderId = $order->getIncrementId();
        $transactionStatusMessage = __('MultiSafepay Transaction status: ') . $transactionStatus;
        $order->addCommentToStatusHistory($transactionStatusMessage);
        $this->logger->logInfoForOrder($orderId, $transactionStatusMessage, Logger::DEBUG);
        $updateOrderStatus = false;

        switch ($transactionStatus) {
            case TransactionStatus::INITIALIZED:
            case TransactionStatus::RESERVED:
            case TransactionStatus::CHARGEDBACK:
            case TransactionStatus::REFUNDED:
            case TransactionStatus::PARTIAL_REFUNDED:
                $updateOrderStatus = true;
                break;
            case TransactionStatus::COMPLETED:
            case TransactionStatus::SHIPPED:
                $this->completeOrderTransaction($order, $payment, $transaction, $transactionManager);
                break;

            case TransactionStatus::UNCLEARED:
                $updateOrderStatus = true;

                if ($gatewayCode !== 'SANTANDER') {
                    $message =
                        __('Uncleared Transaction. You can accept the transaction manually in MultiSafepay Control');
                    $order->addCommentToStatusHistory($message);
                    $this->logger->logInfoForOrder($orderId, $message->render());
                }
                break;

            case TransactionStatus::EXPIRED:
                $updateOrderStatus = true;

                if (in_array($order->getState(), [Order::STATE_PENDING_PAYMENT, Order::STATE_NEW], true)) {
                    $this->cancelOrder($order, $transactionStatusMessage);
                }
                break;
            case TransactionStatus::DECLINED:
            case TransactionStatus::CANCELLED:
            case TransactionStatus::VOID:
                $updateOrderStatus = true;
                $this->cancelOrder($order, $transactionStatusMessage);
                break;
        }

        if ($updateOrderStatus
            && ($statusToUpdate = $this->orderStatusUtil->getOrderStatusByTransactionStatus($order, $transactionStatus))
        ) {
            $orderUpdateStatusMessage = __('Order status has been changed to: %1', $statusToUpdate);
            $this->setOrderStatus($order, $statusToUpdate);
            $order->addCommentToStatusHistory($orderUpdateStatusMessage);
            $this->logger->logInfoForOrder(
                $orderId,
                $orderUpdateStatusMessage->render(),
                Logger::DEBUG
            );
        }
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param array $transaction
     * @param TransactionManager $transactionManager
     * @throws ClientExceptionInterface
     */
    private function completeOrderTransaction(
        OrderInterface $order,
        OrderPaymentInterface $payment,
        array $transaction,
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

        $this->logger->logInfoForOrder(
            $orderId,
            __('MultiSafepay pay order process has been started.')->render(),
            Logger::DEBUG
        );

        $this->payMultisafepayOrder->execute($order, $payment, $transaction);

        $this->logger->logInfoForOrder(
            $orderId,
            __('MultiSafepay pay order process has ended.')->render(),
            Logger::DEBUG
        );

        $this->addInvoicesDataToTransactionAndSendEmail->execute($order, $payment, $transactionManager);

        $this->logger->logInfoForOrder(
            $orderId,
            __('MultiSafepay order transaction complete process has been finished successfully.')->render(),
            Logger::DEBUG
        );
    }

    /**
     * Set the order status by calling the correct function per status.
     *
     * @param OrderInterface $order
     * @param string $status
     * @throws LocalizedException
     * @throws Exception
     */
    private function setOrderStatus(OrderInterface $order, string $status) {
        switch ($status) {
            case Order::STATE_CANCELED:
                $order->cancel();
            break;
            case Order::STATE_HOLDED:
                $order->hold();
            default:
                $order->setStatus($status);
            break;
        }
    }

    /**
     * Cancel the order if it is not already canceled
     *
     * @param OrderInterface $order
     * @param string $transactionStatusMessage
     * @throws LocalizedException
     * @throws Exception
     */
    private function cancelOrder(OrderInterface $order, string $transactionStatusMessage): void
    {
        if ($order->getState() === Order::STATE_CANCELED) {
            return;
        }

        $order->cancel();
        $this->logger->logInfoForOrder(
            $order->getIncrementId(),
            'Order has been canceled. ' . $transactionStatusMessage
        );
    }
}
