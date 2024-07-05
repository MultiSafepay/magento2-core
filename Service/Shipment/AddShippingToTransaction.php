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

namespace MultiSafepay\ConnectCore\Service\Shipment;

use Exception;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use MultiSafepay\Api\Transactions\UpdateRequest;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\ShipmentUtil;
use MultiSafepay\Exception\ApiException;
use Psr\Http\Client\ClientExceptionInterface;

class AddShippingToTransaction
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var UpdateRequest
     */
    private $updateRequest;

    /**
     * @var ShipmentUtil
     */
    private $shipmentUtil;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var SdkFactory
     */
    private $sdkFactory;

    /**
     * AddShippingToTransaction constructor.
     *
     * @param Logger $logger
     * @param UpdateRequest $updateRequest
     * @param ShipmentUtil $shipmentUtil
     * @param ManagerInterface $messageManager
     * @param SdkFactory $sdkFactory
     */
    public function __construct(
        Logger $logger,
        UpdateRequest $updateRequest,
        ShipmentUtil $shipmentUtil,
        ManagerInterface $messageManager,
        SdkFactory $sdkFactory
    ) {
        $this->logger = $logger;
        $this->updateRequest = $updateRequest;
        $this->shipmentUtil = $shipmentUtil;
        $this->messageManager = $messageManager;
        $this->sdkFactory = $sdkFactory;
    }

    /**
     * @param ShipmentInterface $shipment
     * @param OrderInterface $order
     * @throws Exception
     */
    public function execute(
        ShipmentInterface $shipment,
        OrderInterface $order
    ): void {
        $orderId = $order->getIncrementId();
        $updateRequest = $this->updateRequest->addData(
            $this->shipmentUtil->getShipmentApiRequestData($order, $shipment)
        );

        try {
            $this->sdkFactory->create((int)$order->getStoreId())
                ->getTransactionManager()
                ->update($orderId, $updateRequest)
                ->getResponseData();
        } catch (ApiException $apiException) {
            $this->logger->logUpdateRequestApiException($orderId, $apiException);
            $this->messageManager->addErrorMessage(__('The order status could not be updated at MultiSafepay.
                It can be manually updated in MultiSafepay Control'));

            return;
        } catch (ClientExceptionInterface $clientException) {
            $this->logger->logClientException($orderId, $clientException);

            return;
        }

        $this->logger->logInfoForOrder($orderId, 'The shipping status has been updated at MultiSafepay');
        $this->messageManager->addSuccessMessage(__('The order status has succesfully been updated at MultiSafepay'));
    }
}
