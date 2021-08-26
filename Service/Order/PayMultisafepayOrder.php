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

namespace MultiSafepay\ConnectCore\Service\Order;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction as PaymentTransaction;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\CaptureUtil;
use MultiSafepay\ConnectCore\Util\OrderStatusUtil;

class PayMultisafepayOrder
{
    public const INVOICE_CREATE_AFTER_PARAM_NAME = 'multisafepay_create_inovice_after';

    /**
     * @var OrderPaymentRepositoryInterface
     */
    private $orderPaymentRepository;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var OrderStatusUtil
     */
    private $orderStatusUtil;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CaptureUtil
     */
    private $captureUtil;

    /**
     * PayMultisafepayOrder constructor.
     *
     * @param OrderPaymentRepositoryInterface $orderPaymentRepository
     * @param TransactionRepositoryInterface $transactionRepository
     * @param Config $config
     * @param Logger $logger
     * @param OrderStatusUtil $orderStatusUtil
     * @param OrderRepositoryInterface $orderRepository
     * @param CaptureUtil $captureUtil
     */
    public function __construct(
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        TransactionRepositoryInterface $transactionRepository,
        Config $config,
        Logger $logger,
        OrderStatusUtil $orderStatusUtil,
        OrderRepositoryInterface $orderRepository,
        CaptureUtil $captureUtil
    ) {
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->transactionRepository = $transactionRepository;
        $this->config = $config;
        $this->logger = $logger;
        $this->orderStatusUtil = $orderStatusUtil;
        $this->orderRepository = $orderRepository;
        $this->captureUtil = $captureUtil;
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param array $transaction
     * @throws LocalizedException
     */
    public function execute(
        OrderInterface $order,
        OrderPaymentInterface $payment,
        array $transaction
    ): void {
        if ($order->canInvoice()) {
            $invoiceAmount = $order->getBaseTotalDue();
            $orderId = $order->getIncrementId();
            $isManualCaptureTransaction = $this->captureUtil->isCaptureManualTransaction($transaction);

            $isCreateOrderAutomatically = $this->config->isCreateOrderInvoiceAutomatically($order->getStoreId());
            $payment->setTransactionId($transaction['transaction_id'] ?? '')
                ->setAdditionalInformation(
                    [
                        PaymentTransaction::RAW_DETAILS => (array)$payment->getAdditionalInformation(),
                        self::INVOICE_CREATE_AFTER_PARAM_NAME => !$isCreateOrderAutomatically,
                    ]
                )->setShouldCloseParentTransaction(false)
                ->setIsTransactionClosed(0)
                ->setIsTransactionPending(false);

            if (!$isManualCaptureTransaction) {
                $this->createInvoice($isCreateOrderAutomatically, $payment, $invoiceAmount, $orderId);
            }

            $payment->setParentTransactionId($transaction['transaction_id'] ?? '');
            $payment->setIsTransactionApproved(true);
            $this->logger->logInfoForOrder($orderId, 'Order payment was updated', Logger::DEBUG);
            $paymentTransaction = $payment->addTransaction(
                $isManualCaptureTransaction ? PaymentTransaction::TYPE_AUTH
                    : PaymentTransaction::TYPE_CAPTURE,
                null,
                true
            );

            if ($paymentTransaction !== null) {
                $paymentTransaction->setParentTxnId($transaction['transaction_id'] ?? '');
            }

            if (!$isManualCaptureTransaction) {
                $paymentTransaction->setIsClosed(1);
            }

            $this->transactionRepository->save($paymentTransaction);
            $this->logger->logInfoForOrder($orderId, 'Transaction saved', Logger::DEBUG);

            if (!$isCreateOrderAutomatically) {
                $order->addCommentToStatusHistory(
                    __(
                        'Captured amount %1 by MultiSafepay. Transaction ID: "%2"',
                        $order->getBaseCurrency()->formatTxt($invoiceAmount),
                        $paymentTransaction->getTxnId()
                    )
                );
            }

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
     * @param bool $isCreateOrderAutomatically
     * @param OrderPaymentInterface $payment
     * @param float $captureAmount
     * @param string $orderId
     */
    private function createInvoice(
        bool $isCreateOrderAutomatically,
        OrderPaymentInterface $payment,
        float $captureAmount,
        string $orderId
    ): void {
        if ($isCreateOrderAutomatically) {
            $payment->registerCaptureNotification($captureAmount, true);
            $this->logger->logInfoForOrder($orderId, 'Invoice created', Logger::DEBUG);

            return;
        }

        $this->logger->logInfoForOrder(
            $orderId,
            'Invoice creation process was skipped by selected setting.',
            Logger::DEBUG
        );
    }
}
