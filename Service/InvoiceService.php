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
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\MailException;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction as PaymentTransaction;
use MultiSafepay\Api\Transactions\TransactionResponse as Transaction;
use MultiSafepay\ConnectCore\Logger\Logger;

class InvoiceService
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    private $orderPaymentRepository;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * @var EmailSender
     */
    private $emailSender;

    /**
     * InvoiceService constructor.
     *
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param OrderPaymentRepositoryInterface $orderPaymentRepository
     * @param TransactionRepositoryInterface $transactionRepository
     * @param TransactionFactory $transactionFactory
     * @param Logger $logger
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param EmailSender $emailSender
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        InvoiceRepositoryInterface $invoiceRepository,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        TransactionRepositoryInterface $transactionRepository,
        TransactionFactory $transactionFactory,
        Logger $logger,
        OrderItemRepositoryInterface $orderItemRepository,
        EmailSender $emailSender
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->invoiceRepository = $invoiceRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->transactionRepository = $transactionRepository;
        $this->transactionFactory = $transactionFactory;
        $this->logger = $logger;
        $this->orderItemRepository = $orderItemRepository;
        $this->emailSender = $emailSender;
    }

    /**
     * @param OrderInterface $order
     * @param ShipmentInterface $shipment
     * @param OrderPaymentInterface $payment
     * @return bool
     * @throws Exception
     */
    public function createInvoiceAfterShipment(
        OrderInterface $order,
        ShipmentInterface $shipment,
        OrderPaymentInterface $payment
    ): bool {
        $orderIncrementId = $order->getIncrementId();
        $invoiceData = $this->getInvoiceItemsQtyDataFromShipment($shipment);

        if ($order->canInvoice() && $invoiceData) {
            $payment->setShipment($shipment);
            $invoice = $this->createInvoiceByInvoiceData($order, $payment, $invoiceData);
            $invoiceId = $invoice->getIncrementId();
            $this->logger->logInfoForOrder($orderIncrementId, __('Manual capture invoice %1 was created.', $invoiceId)
                ->render());

            return true;
        }

        return false;
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param array $invoiceData
     * @return InvoiceInterface
     * @throws Exception
     */
    public function createInvoiceByInvoiceData(
        OrderInterface $order,
        OrderPaymentInterface $payment,
        array $invoiceData
    ): InvoiceInterface {
        if (!$payment->canCapture() || !$payment->canCapturePartial()) {
            throw new Exception("Invoice can't be captured");
        }

        /** @var InvoiceInterface $invoice */
        $invoice = $order->prepareInvoice($invoiceData);
        $invoice->register();
        $invoice->getOrder()->setIsInProcess(true);
        $payment->setInvoice($invoice);
        $payment->capture($invoice);

        if ($invoice->getIsPaid()) {
            $invoice->pay();
        }

        $payment->getOrder()->addRelatedObject($invoice);
        $payment->setCreatedInvoice($invoice);
        $this->transactionFactory->create()
            ->addObject($invoice)
            ->addObject($invoice->getOrder())
            ->save();

        return $invoice;
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param array $transaction
     * @param float|null $invoiceAmount
     * @return bool
     */
    public function payOrderByAmount(
        OrderInterface $order,
        OrderPaymentInterface $payment,
        array $transaction,
        ?float $invoiceAmount
    ): bool {
        if ($order->canInvoice()) {
            $isCreateOrderAutomatically = $this->config->isCreateOrderInvoiceAutomatically($order->getStoreId());

            $orderId = $order->getIncrementId();
            $payment->setTransactionId($transaction['transaction_id'] ?? '')
                ->setAdditionalInformation(
                    [PaymentTransaction::RAW_DETAILS => (array)$payment->getAdditionalInformation()]
                )->setShouldCloseParentTransaction(false)
                ->setIsTransactionClosed(0)
                ->setIsTransactionPending(false);

            $this->createInvoice($isCreateOrderAutomatically, $payment, $invoiceAmount, $orderId);

            $this->logger->logInfoForOrder($orderId, 'Invoice created', Logger::DEBUG);
            $payment->setParentTransactionId($transaction['transaction_id'] ?? '');
            $payment->setIsTransactionApproved(true);
            $this->orderPaymentRepository->save($payment);
            $this->logger->logInfoForOrder($orderId, 'Payment saved', Logger::DEBUG);
            $paymentTransaction = $payment->addTransaction(
                PaymentTransaction::TYPE_CAPTURE,
                null,
                true
            );

            if ($paymentTransaction !== null) {
                $paymentTransaction->setParentTxnId($transaction['transaction_id'] ?? '');
            }

            $paymentTransaction->setIsClosed(1);
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

            return true;
        }

        return false;
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

    /**
     * @param OrderPaymentInterface $payment
     * @param InvoiceInterface $invoice
     * @throws Exception
     */
    public function sendInvoiceEmail(OrderPaymentInterface $payment, InvoiceInterface $invoice): void
    {
        try {
            $orderIncrementId = $invoice->getOrder()->getIncrementId();
            if ($this->emailSender->sendInvoiceEmail($payment, $invoice)) {
                $this->logger->logInfoForOrder(
                    $orderIncrementId,
                    __('Email for invoice %1 was sent.', $invoice->getIncrementId())->render()
                );
            }
        } catch (MailException $mailException) {
            $this->logger->logExceptionForOrder($orderIncrementId, $mailException, Logger::INFO);
        }
    }

    /**
     * @param string $orderId
     * @return InvoiceInterface[]
     */
    public function getInvoicesByOrderId(string $orderId): array
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('order_id', $orderId)->create();

        return $this->invoiceRepository->getList($searchCriteria)->getItems();
    }

    /**
     * @param string $orderId
     * @return InvoiceInterface|null
     */
    public function getLastCreatedInvoiceByOrderId(string $orderId): ?InvoiceInterface
    {
        if ($invoices = $this->getInvoicesByOrderId($orderId)) {
            return end($invoices);
        }

        return null;
    }

    /**
     * @param ShipmentInterface $shipment
     * @return array
     */
    private function getInvoiceItemsQtyDataFromShipment(ShipmentInterface $shipment): array
    {
        $invoiceData = [];

        foreach ($shipment->getItems() as $item) {
            $orderItemId = (int)$item->getOrderItemId();
            $shippedQty = (float)$item->getQty();
            $orderItem = $this->orderItemRepository->get($orderItemId);
            $orderQtyToInvoice = $orderItem->getQtyToInvoice();
            $canInvoiceQty = ($shippedQty <= $orderQtyToInvoice) ? $shippedQty : $orderQtyToInvoice;

            if ($canInvoiceQty) {
                $invoiceData[$orderItemId] = $item->getQty();
            }
        }

        return $invoiceData;
    }
}
