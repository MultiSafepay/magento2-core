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

namespace MultiSafepay\ConnectCore\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use MultiSafepay\Api\Transactions\UpdateRequest;
use MultiSafepay\Api\Transactions\CaptureRequest;
use MultiSafepay\ConnectAdminhtml\Model\Config\Source\PaymentAction;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;
use MultiSafepay\Exception\ApiException;
use Psr\Http\Client\ClientExceptionInterface;
use MultiSafepay\ConnectCore\Util\PriceUtil;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\Quote\Model\Cart\ShippingMethodConverter;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use MultiSafepay\ConnectCore\Service\OrderService;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\InvoiceManagementInterface;
use Magento\Framework\DB\TransactionFactory;

class ShipmentSaveAfterObserver implements ObserverInterface
{

    /**
     * @var SdkFactory
     */
    private $sdkFactory;

    /**
     * @var UpdateRequest
     */
    private $updateRequest;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var PriceUtil
     */
    private $priceUtil;

    /**
     * @var CaptureRequest
     */
    private $captureRequest;

    /**
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var ResolverInterface
     */
    private $resolver;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var TotalsCollector
     */
    private $totalsCollector;

    /**
     * @var ShippingMethodConverter
     */
    private $shippingMethodConverter;

    /**
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * @var AddressInterfaceFactory
     */
    private $addressInterfaceFactory;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    private $orderPaymentRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var InvoiceManagementInterface
     */
    private $invoiceManagement;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    public function __construct(
        SdkFactory $sdkFactory,
        Logger $logger,
        ManagerInterface $messageManager,
        UpdateRequest $updateRequest,
        PaymentMethodUtil $paymentMethodUtil,
        PriceUtil $priceUtil,
        CaptureRequest $captureRequest,
        OrderItemRepositoryInterface $orderItemRepository,
        CartRepositoryInterface $cartRepository,
        ResolverInterface $resolver,
        QuoteFactory $quoteFactory,
        TotalsCollector $totalsCollector,
        ShippingMethodConverter $shippingMethodConverter,
        DataObjectProcessor $dataObjectProcessor,
        AddressInterfaceFactory $addressInterfaceFactory,
        TransactionRepositoryInterface $transactionRepository,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        InvoiceRepositoryInterface $invoiceRepository,
        OrderService $orderService,
        OrderRepositoryInterface $orderRepository,
        InvoiceManagementInterface $invoiceManagement,
        TransactionFactory $transactionFactory
    ) {
        $this->sdkFactory = $sdkFactory;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->updateRequest = $updateRequest;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->priceUtil = $priceUtil;
        $this->captureRequest = $captureRequest;
        $this->orderItemRepository = $orderItemRepository;
        $this->cartRepository = $cartRepository;
        $this->resolver = $resolver;
        $this->quoteFactory = $quoteFactory;
        $this->totalsCollector = $totalsCollector;
        $this->shippingMethodConverter = $shippingMethodConverter;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->addressInterfaceFactory = $addressInterfaceFactory;
        $this->transactionRepository = $transactionRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->invoiceRepository = $invoiceRepository;
        $this->orderService = $orderService;
        $this->orderRepository = $orderRepository;
        $this->invoiceManagement = $invoiceManagement;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * @param Observer $observer
     * @throws ClientExceptionInterface
     */
    public function execute(Observer $observer): void
    {
        $event = $observer->getEvent();

        /** @var ShipmentInterface $shipment */
        $shipment = $event->getShipment();
        $order = $shipment->getOrder();
        $this->addShippingToTransaction($shipment, $order);
    }

    /**
     * @param ShipmentInterface $shipment
     * @param OrderInterface $order
     * @throws ClientExceptionInterface
     */
    public function addShippingToTransaction(
        ShipmentInterface $shipment,
        OrderInterface $order
    ): void {
        if ($this->paymentMethodUtil->isMultisafepayOrder($order)) {
            $transactionManager = $this->sdkFactory->create((int)$order->getStoreId())->getTransactionManager();
            $payment = $order->getPayment();
            $orderId = $order->getIncrementId();
            $transaction = $transactionManager->get($orderId);

            if ($payment->getMethod() === 'multisafepay_visa'
                && $payment->getMethodInstance()->getConfigPaymentAction() === PaymentAction::PAYMENT_ACTION_AUTHORIZE_ONLY
            ) {
                if ((float)$order->getTotalQtyOrdered() === (float)$shipment->getTotalQty()) {
                    $amount = $order->getTotalDue();
                } else {
                    $amount = $this->captureAmount($shipment, $order);
                    $invoiceData = [];

                    foreach ($shipment->getItems() as $item) {
                        $invoiceData[$item->getOrderItemId()] = $item->getQty();
                    }

                    $invoice = $this->invoiceManagement->prepareInvoice($order, $invoiceData);
                    $invoice->register();
                    $invoice->getOrder()->setIsInProcess(true);

                    $transactionSave = $this->transactionFactory->create()->addObject(
                        $invoice
                    )->addObject(
                        $invoice->getOrder()
                    );

                    $transactionSave->save();
                }

                //$this->orderService->invoiceByAmount($order, $payment, $transaction, $amount);
                $this->orderRepository->save($order);
                $array = $this->orderService->getInvoicesByOrderId($order->getId());
                $invoice = reset($array);

                $captureRequest = $this->captureRequest->addData(
                    [
                        "amount" => round($amount * 100, 10),
                        "new_order_status" => "completed",
                        "invoice_id" => $invoice ? $invoice->getIncrementId() : "",
                        "carrier" => $order->getShippingDescription(),
                        "reason" => "Shipped",
                        "memo" => "",
                    ]
                );

                $transactionManager->capture($orderId, $captureRequest)->getResponseData();
            }

            $updateRequest = $this->updateRequest->addData([
                "tracktrace_code" => $this->getTrackingNumber($shipment),
                "carrier" => $order->getShippingDescription(),
                "ship_date" => $shipment->getCreatedAt(),
                "reason" => 'Shipped',
            ]);

            try {
                $transactionManager->update($orderId, $updateRequest)->getResponseData();
            } catch (ApiException $apiException) {
                $this->logger->logUpdateRequestApiException($orderId, $apiException);

                $msg = __('The order status could not be updated at MultiSafepay.
                It can be manually updated in MultiSafepay Control');

                $this->messageManager->addErrorMessage($msg);

                return;
            }

            $msg = __('The order status has succesfully been updated at MultiSafepay');
            $this->messageManager->addSuccessMessage($msg);
        }
    }

    public function captureAmount($shipping, $order): float
    {
        $unitPrice = 0;

        foreach ($shipping->getItems() as $item) {
            $orderItem = $this->orderItemRepository->get($item->getOrderItemId());
            $unitPrice += ($orderItem->getPrice() + ($orderItem->getTaxAmount() / $orderItem->getQtyOrdered()) -
                           ($orderItem->getDiscountAmount() / $orderItem->getQtyOrdered())) * $item->getQty();

            if ($this->isFirstShpment($order)) {
                $unitPrice += $order->getShippingInclTax();
            }
        }

        return (float)$unitPrice;
    }

    /**
     * @param ShipmentInterface $shipment
     * @return string
     */
    public function getTrackingNumber(ShipmentInterface $shipment): string
    {
        if (empty($shipment->getTracks())) {
            return '';
        }

        return $shipment->getTracks()[0]->getTrackNumber();
    }

    /**
     * @param OrderInterface $order
     * @return bool
     */
    public function isFirstShpment(OrderInterface $order): bool
    {
        return $order->getShipmentsCollection()->getSize() <= 1;
    }
}
