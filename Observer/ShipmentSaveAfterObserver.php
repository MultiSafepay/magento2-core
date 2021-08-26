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
use Magento\Sales\Api\Data\ShipmentInterface;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Service\Shipment\AddShippingToTransaction;
use MultiSafepay\ConnectCore\Service\Shipment\ProcessManualCaptureShipment;
use MultiSafepay\ConnectCore\Util\CaptureUtil;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;
use Psr\Http\Client\ClientExceptionInterface;

class ShipmentSaveAfterObserver implements ObserverInterface
{
    /**
     * @var SdkFactory
     */
    private $sdkFactory;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var CaptureUtil
     */
    private $captureUtil;

    /**
     * @var AddShippingToTransaction
     */
    private $addShippingToTransaction;

    /**
     * @var ProcessManualCaptureShipment
     */
    private $processManualCaptureShipment;

    /**
     * ShipmentSaveAfterObserver constructor.
     *
     * @param SdkFactory $sdkFactory
     * @param PaymentMethodUtil $paymentMethodUtil
     * @param CaptureUtil $captureUtil
     * @param AddShippingToTransaction $addShippingToTransaction
     * @param ProcessManualCaptureShipment $processManualCaptureShipment
     */
    public function __construct(
        SdkFactory $sdkFactory,
        PaymentMethodUtil $paymentMethodUtil,
        CaptureUtil $captureUtil,
        AddShippingToTransaction $addShippingToTransaction,
        ProcessManualCaptureShipment $processManualCaptureShipment
    ) {
        $this->sdkFactory = $sdkFactory;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->captureUtil = $captureUtil;
        $this->addShippingToTransaction = $addShippingToTransaction;
        $this->processManualCaptureShipment = $processManualCaptureShipment;
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

        if (!$this->paymentMethodUtil->isMultisafepayOrder($order)) {
            return;
        }

        if ($this->captureUtil->isCaptureManualTransaction(
            $this->sdkFactory->create((int)$order->getStoreId())
                ->getTransactionManager()
                ->get($order->getIncrementId())
                ->getData()
        )) {
            $this->processManualCaptureShipment->execute($shipment, $order, $order->getPayment());

            return;
        }

        $this->addShippingToTransaction->execute($shipment, $order);
    }
}
