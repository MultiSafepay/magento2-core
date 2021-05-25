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
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;
use MultiSafepay\Exception\ApiException;
use Psr\Http\Client\ClientExceptionInterface;

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
     * ShipmentSaveAfterObserver constructor.
     *
     * @param SdkFactory $sdkFactory
     * @param Logger $logger
     * @param ManagerInterface $messageManager
     * @param UpdateRequest $updateRequest
     * @param PaymentMethodUtil $paymentMethodUtil
     */
    public function __construct(
        SdkFactory $sdkFactory,
        Logger $logger,
        ManagerInterface $messageManager,
        UpdateRequest $updateRequest,
        PaymentMethodUtil $paymentMethodUtil
    ) {
        $this->sdkFactory = $sdkFactory;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->updateRequest = $updateRequest;
        $this->paymentMethodUtil = $paymentMethodUtil;
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

            $updateRequest = $this->updateRequest->addData([
                    "tracktrace_code" => $this->getTrackingNumber($shipment),
                    "carrier" => $order->getShippingDescription(),
                    "ship_date" => $shipment->getCreatedAt(),
                    "reason" => 'Shipped'
                ]);

            $orderId = $order->getIncrementId();

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

    /**
     * @param ShipmentInterface $shipment
     * @return string
     */
    public function getTrackingNumber(ShipmentInterface $shipment): string
    {
        if (!($tracks = $shipment->getTracks())) {
            return '';
        }

        return is_array($tracks) ? reset($tracks)->getTrackNumber() : '';
    }
}
