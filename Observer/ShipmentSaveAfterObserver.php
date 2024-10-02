<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * See DISCLAIMER.md for disclaimer details.
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Observer;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Shipment;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\Shipment\AddShippingToTransaction;
use MultiSafepay\ConnectCore\Service\Shipment\ProcessManualCaptureShipment;
use MultiSafepay\ConnectCore\Util\CaptureUtil;
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
     * @var Logger
     */
    private $logger;

    /**
     * ShipmentSaveAfterObserver constructor.
     *
     * @param SdkFactory $sdkFactory
     * @param PaymentMethodUtil $paymentMethodUtil
     * @param CaptureUtil $captureUtil
     * @param AddShippingToTransaction $addShippingToTransaction
     * @param ProcessManualCaptureShipment $processManualCaptureShipment
     * @param Logger $logger
     */
    public function __construct(
        SdkFactory $sdkFactory,
        PaymentMethodUtil $paymentMethodUtil,
        CaptureUtil $captureUtil,
        AddShippingToTransaction $addShippingToTransaction,
        ProcessManualCaptureShipment $processManualCaptureShipment,
        Logger $logger
    ) {
        $this->sdkFactory = $sdkFactory;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->captureUtil = $captureUtil;
        $this->addShippingToTransaction = $addShippingToTransaction;
        $this->processManualCaptureShipment = $processManualCaptureShipment;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     * @throws Exception
     * @throws ClientExceptionInterface
     */
    public function execute(Observer $observer): void
    {
        $event = $observer->getEvent();

        /** @var Shipment $shipment */
        $shipment = $event->getShipment();

        $order = $shipment->getOrder();
        $orderId = $order->getIncrementId();

        if (!$this->paymentMethodUtil->isMultisafepayOrder($order)) {
            return;
        }

        try {
            if ($this->captureUtil->isCaptureManualTransaction(
                $this->sdkFactory->create((int)$order->getStoreId())
                    ->getTransactionManager()
                    ->get($order->getIncrementId())
                    ->getData()
            )) {
                $this->processManualCaptureShipment->execute($shipment, $order, $order->getPayment());

                return;
            }
        } catch (ApiException | ClientExceptionInterface $exception) {
            $this->logger->logExceptionForOrder($orderId, $exception);

            throw new LocalizedException(__(
                'The manual capture could not be created at MultiSafepay, please check the logs.'
            ));
        }

        $this->addShippingToTransaction->execute($shipment, $order);
    }
}
