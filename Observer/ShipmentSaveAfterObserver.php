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
use Magento\Framework\DataObject;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\Quote\Model\Cart\ShippingMethodConverter;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\AddressInterfaceFactory;

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
        AddressInterfaceFactory $addressInterfaceFactory
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

            if ($payment->getMethod() === 'multisafepay_visa'
                && $payment->getMethodInstance()->getConfigPaymentAction() === PaymentAction::PAYMENT_ACTION_AUTHORIZE_ONLY
            ) {
                $shippingAmount = $this->getShippingAmount($shipment, $order);
                $captureRequest = $this->captureRequest->addData(
                    [
                        "amount" => (int)$shippingAmount,
                        "new_order_status" => "completed",
                        "invoice_id" => "",
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

    public function getShippingAmount($shipping, $order): float
    {
        //$currency = (string)$order->getOrderCurrencyCode();
        $storeId = $order->getStoreId();
        $items = [];
        $shippingItems = $shipping->getItems();
        $amount = 0;
        $quote = $this->cartRepository->get($order->getQuoteId());
        $shippingAddress = $quote->getShippingAddress();

        foreach ($shippingItems as $item) {
            $orderItem = $this->orderItemRepository->get($item->getOrderItemId());
            $shippingAmount = $this->calculateShippingAmountPerItem($orderItem, $item->getQty(), $shippingAddress,
                $storeId, $order->getShippingMethod());

            $unitPrice = $orderItem->getPrice() + $orderItem->getTaxAmount() - $orderItem->getDiscountAmount() + $shippingAmount;
            $amount += round($unitPrice * 100, 10);
        }

        return (float)$amount;
    }

    /**
     * @param $orderItem
     * @param $qty
     * @param $shippingAddress
     * @return array
     */
    private function calculateShippingAmountPerItem($orderItem, $qty, $shippingAddress, $storeId, $shippingMethod)
    {
        $params = [];

        if ($qty) {
            //$filter = new \Zend_Filter_LocalizedToNormalized(
            //    ['locale' => $this->resolver->getLocale()]
            //);

            //$params['qty'] = $filter->filter($qty);
            $params['qty'] = $qty;
        }

        $requestData = new DataObject($params);

        $quote = $this->quoteFactory->create();
        $quote->addProduct($orderItem->getProduct(), $requestData);
        $quote->setStoreId($storeId);
        $newAddress = $this->addressInterfaceFactory->create()
            ->setPostcode($shippingAddress->getPostcode())
            ->setCountryId($shippingAddress->getCountryId())
            ->setRegion($shippingAddress->getRegion())
            ->setRegionId($shippingAddress->getRegionId());

        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->addData($this->extractAddress($newAddress));
        $shippingAddress->setCollectShippingRates(true);

        $this->totalsCollector->collectAddressTotals($quote, $shippingAddress);
        $shippingRates = $shippingAddress->getGroupedAllShippingRates();

        foreach ($shippingRates as $carrierRates) {
            foreach ($carrierRates as $rate) {
                if ($rate->getCode() === $shippingMethod) {
                    return $rate->getPrice();
                }
            }
        }

        return $params;
    }

    /**
     * @param $address
     * @return array
     */
    private function extractAddress($address)
    {
        return $this->dataObjectProcessor->buildOutputDataArray(
            $address,
            AddressInterface::class
        );
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
}
