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
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\InvoiceManagementInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Operations\ProcessInvoiceOperation;
use Magento\Sales\Model\Order\Payment\Transaction as PaymentTransaction;
use MultiSafepay\Api\Transactions\CaptureRequest;
use MultiSafepay\Api\Transactions\TransactionResponse as Transaction;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\AmountUtil;
use MultiSafepay\ConnectCore\Util\PriceUtil;
use Psr\Http\Client\ClientExceptionInterface;

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
     * @var ProcessInvoiceOperation
     */
    private $processInvoiceOperation;

    /**
     * @var InvoiceManagementInterface
     */
    private $invoiceManagement;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SdkFactory
     */
    private $sdkFactory;

    /**
     * @var PriceUtil
     */
    private $priceUtil;

    /**
     * @var CaptureRequest
     */
    private $captureRequest;

    /**
     * @var AmountUtil
     */
    private $amountUtil;

    /**
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        InvoiceRepositoryInterface $invoiceRepository,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        TransactionRepositoryInterface $transactionRepository,
        ProcessInvoiceOperation $processInvoiceOperation,
        InvoiceManagementInterface $invoiceManagement,
        TransactionFactory $transactionFactory,
        Logger $logger,
        SdkFactory $sdkFactory,
        PriceUtil $priceUtil,
        CaptureRequest $captureRequest,
        AmountUtil $amountUtil,
        OrderItemRepositoryInterface $orderItemRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->invoiceRepository = $invoiceRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->transactionRepository = $transactionRepository;
        $this->processInvoiceOperation = $processInvoiceOperation;
        $this->invoiceManagement = $invoiceManagement;
        $this->transactionFactory = $transactionFactory;
        $this->logger = $logger;
        $this->sdkFactory = $sdkFactory;
        $this->priceUtil = $priceUtil;
        $this->captureRequest = $captureRequest;
        $this->amountUtil = $amountUtil;
        $this->orderItemRepository = $orderItemRepository;
    }

    /**
     * @param OrderInterface $order
     * @param ShipmentInterface $shipment
     * @param OrderPaymentInterface $payment
     * @return bool
     * @throws ClientExceptionInterface
     * @throws LocalizedException
     */
    public function createInvoiceAfterShipment(
        OrderInterface $order,
        ShipmentInterface $shipment,
        OrderPaymentInterface $payment
    ): bool {
        $orderIncrementId = $order->getIncrementId();
        $transactionManager = $this->sdkFactory->create((int)$order->getStoreId())->getTransactionManager();
        $transaction = $transactionManager->get($orderIncrementId);
        $invoiceData = [];

        foreach ($shipment->getItems() as $item) {
            $invoiceData[$item->getOrderItemId()] = $item->getQty();
        }

        if ($order->canInvoice()) {
            $payment->setShipment($shipment);
            $this->createInvoiceByInvoiceData($order, $payment, $transaction, $invoiceData);
        }

        return true;
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param Transaction $transaction
     * @param array $invoiceData
     * @return bool
     * @throws Exception
     */
    public function createInvoiceByInvoiceData(
        OrderInterface $order,
        OrderPaymentInterface $payment,
        Transaction $transaction,
        array $invoiceData
    ): bool {
        if (!$payment->canCapture() || !$payment->canCapturePartial()) {
            throw new Exception("Invoice can't be created");
        }

        /** @var InvoiceInterface $invoice */
        $invoice = $this->invoiceManagement->prepareInvoice($order, $invoiceData);
        $invoice->register();
        $invoice->getOrder()->setIsInProcess(true);
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

        return true;
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param Transaction $transaction
     * @param $invoiceAmount
     * @return bool
     */
    public function invoiceByAmount(
        OrderInterface $order,
        OrderPaymentInterface $payment,
        Transaction $transaction,
        $invoiceAmount
    ): bool {
        if ($order->canInvoice()) {
            $payment->setTransactionId($transaction->getData()['transaction_id'])
                ->setAdditionalInformation(
                    [PaymentTransaction::RAW_DETAILS => (array)$payment->getAdditionalInformation()]
                )->setShouldCloseParentTransaction(false)
                ->setIsTransactionClosed(0)
                ->registerCaptureNotification($invoiceAmount, true);

            $payment->setParentTransactionId($transaction->getData()['transaction_id']);
            $payment->setIsTransactionApproved(true);
            $this->orderPaymentRepository->save($payment);
            $this->logger->logInfoForOrder($order->getIncrementId(), 'Invoice created');
            $paymentTransaction = $payment->addTransaction(PaymentTransaction::TYPE_CAPTURE, null, true);

            if ($paymentTransaction !== null) {
                $paymentTransaction->setParentTxnId($transaction->getData()['transaction_id']);
            }

            $paymentTransaction->setIsClosed(1);
            $this->transactionRepository->save($paymentTransaction);

            return true;
        }

        return false;
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
            return reset($invoices);
        }

        return null;
    }

    /**
     * @param ShipmentInterface $shipment
     * @param OrderInterface $order
     * @return float
     */
    public function getInvoiceAmountAfterShipping(ShipmentInterface $shipment, OrderInterface $order): float
    {
        $unitPrice = 0;
        $storeId = $order->getStoreId();

        foreach ($shipment->getItems() as $item) {
            $orderItem = $this->orderItemRepository->get($item->getOrderItemId());
            $unitPrice += (float)($this->priceUtil->getUnitRowItemPriceWithTax($orderItem, $storeId) * $item->getQty());

            if ($this->isFirstShipmentForOrder($order)) {
                $unitPrice += $this->priceUtil->getShippingUnitPrice($order);
            }
        }

        return (float)$unitPrice;
    }

    /**
     * @param OrderInterface $order
     * @return bool
     */
    public function isFirstShipmentForOrder(OrderInterface $order): bool
    {
        return (bool)($order->getShipmentsCollection()->getSize() <= 1);
    }
}
