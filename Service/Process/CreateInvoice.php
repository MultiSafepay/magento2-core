<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * Copyright © 2022 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Service\Process;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction as PaymentTransaction;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\CaptureUtil;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class CreateInvoice implements ProcessInterface
{
    public const INVOICE_CREATE_AFTER = 'multisafepay_create_inovice_after';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CaptureUtil
     */
    private $captureUtil;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * CreateInvoice constructor
     *
     * @param CaptureUtil $captureUtil
     * @param Config $config
     * @param Logger $logger
     * @param TransactionRepositoryInterface $transactionRepository
     */
    public function __construct(
        CaptureUtil $captureUtil,
        Config $config,
        Logger $logger,
        TransactionRepositoryInterface $transactionRepository
    ) {
        $this->captureUtil = $captureUtil;
        $this->config = $config;
        $this->logger = $logger;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * Process the creation of the invoice
     *
     * @param OrderInterface $order
     * @param array $transaction
     * @return array|bool[]
     * @throws Exception
     */
    public function execute(OrderInterface $order, array $transaction): array
    {
        $orderId = $order->getIncrementId();

        if (!$order->canInvoice()) {
            $message = 'Order can not be invoiced';
            $this->logger->logInfoForNotification($orderId, $message, $transaction);
            return [
                StatusOperationInterface::SUCCESS_PARAMETER => false,
                StatusOperationInterface::MESSAGE_PARAMETER => $message
            ];
        }

        $payment = $order->getPayment();

        if ($payment === null) {
            $message = 'Order can not be invoiced, because the payment was not found';
            $this->logger->logInfoForNotification($orderId, $message, $transaction);
            return [
                StatusOperationInterface::SUCCESS_PARAMETER => false,
                StatusOperationInterface::MESSAGE_PARAMETER => $message
            ];
        }

        $invoiceAmount = $order->getBaseTotalDue();
        $isManualCaptureTransaction = $this->captureUtil->isCaptureManualTransaction($transaction);

        $automaticallyCreateInvoice = $this->config->isCreateOrderInvoiceAutomatically($order->getStoreId());
        $payment->setTransactionId($transaction['transaction_id'] ?? '')
            ->setAdditionalInformation(
                [
                    PaymentTransaction::RAW_DETAILS => (array)$payment->getAdditionalInformation(),
                    self::INVOICE_CREATE_AFTER => !$automaticallyCreateInvoice,
                ]
            )->setShouldCloseParentTransaction(false)
            ->setIsTransactionClosed(0)
            ->setIsTransactionPending(false);

        if (!$isManualCaptureTransaction) {
            $this->createInvoice($automaticallyCreateInvoice, $payment, $invoiceAmount, $orderId, $transaction);
        }

        $payment->setParentTransactionId($transaction['transaction_id'] ?? '');
        $this->logger->logInfoForNotification($orderId, 'Order payment was updated', $transaction);
        $paymentTransaction = $payment->addTransaction(
            $isManualCaptureTransaction ? PaymentTransaction::TYPE_AUTH
                : PaymentTransaction::TYPE_CAPTURE,
            null,
            true
        );

        if ($paymentTransaction !== null) {
            try {
                $paymentTransaction->setParentTxnId($transaction['transaction_id'] ?? '');
            } catch (LocalizedException $localizedException) {
                $message = 'Exception occurred when trying to set the parent transaction ID';
                $this->logger->logInfoForNotification($order->getIncrementId(), $message, $transaction);
            }

        }

        if (!$isManualCaptureTransaction) {
            $paymentTransaction->setIsClosed(1);
        }

        $this->transactionRepository->save($paymentTransaction);
        $this->logger->logInfoForNotification($orderId, 'Transaction saved', $transaction);

        if (!$automaticallyCreateInvoice) {
            $order->addCommentToStatusHistory(
                __(
                    'Captured amount %1 by MultiSafepay. Transaction ID: "%2"',
                    $order->getBaseCurrency()->formatTxt($invoiceAmount),
                    $paymentTransaction->getTxnId()
                )
            );
        }

        return [StatusOperationInterface::SUCCESS_PARAMETER => true];
    }

    /**
     * Create the invoice
     *
     * @param bool $automaticallyCreateInvoice
     * @param OrderPaymentInterface $payment
     * @param float $captureAmount
     * @param string $orderId
     * @param array $transaction
     * @throws Exception
     */
    private function createInvoice(
        bool $automaticallyCreateInvoice,
        OrderPaymentInterface $payment,
        float $captureAmount,
        string $orderId,
        array $transaction
    ): void {
        if ($automaticallyCreateInvoice) {
            $payment->registerCaptureNotification($captureAmount, true);
            $this->logger->logInfoForNotification($orderId, 'Invoice created', $transaction);

            return;
        }

        $this->logger->logInfoForNotification(
            $orderId,
            'Invoice creation process was skipped by selected setting.',
            $transaction
        );
    }
}
