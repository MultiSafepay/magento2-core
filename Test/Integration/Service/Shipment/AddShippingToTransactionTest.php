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

namespace MultiSafepay\ConnectCore\Test\Integration\Service\Shipment;

use Exception;
use Magento\Framework\Message\ManagerInterface;
use MultiSafepay\Api\Base\Response;
use MultiSafepay\Api\TransactionManager;
use MultiSafepay\Api\Transactions\UpdateRequest;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\Shipment\AddShippingToTransaction;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\ShipmentUtil;
use MultiSafepay\Sdk;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddShippingToTransactionTest extends AbstractTestCase
{
    /**
     * @var UpdateRequest
     */
    private $updateRequest;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->updateRequest = $this->getObjectManager()->get(UpdateRequest::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/multisafepay_visa/payment_action authorize
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @magentoConfigFixture default_store multisafepay/general/use_manual_capture 1
     * @throws Exception
     * @throws ClientExceptionInterface
     */
    public function testCreateFullShipmentForManualCaptureCreatedOrder()
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $shipmentItems = [];

        foreach ($order->getItems() as $orderItem) {
            $shipmentItems[$orderItem->getId()] = $orderItem->getQtyOrdered();
        }

        $shipment = $this->createShipmentForOrder($order, $shipmentItems);
        $addShippingToTransactionService = $this->getMockBuilder(AddShippingToTransaction::class)
            ->setConstructorArgs([
                $this->getObjectManager()->get(Logger::class),
                $this->updateRequest,
                $this->getObjectManager()->get(ShipmentUtil::class),
                $this->getObjectManager()->get(ManagerInterface::class),
                $this->setupSdkFactory($this->getSdkMock($order->getIncrementId())),
            ])
            ->setMethodsExcept(['execute'])
            ->getMock();

        $addShippingToTransactionService->execute($shipment, $order);
    }

    /**
     * @param string $orderId
     * @return MockObject
     */
    private function getSdkMock(
        string $orderId
    ): MockObject {
        $sdk = $this->getMockBuilder(Sdk::class)
            ->disableOriginalConstructor()
            ->getMock();

        $transactionManagerMock = $this->getMockBuilder(TransactionManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockResponse = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockResponse->expects(self::once())
            ->method('getResponseData')
            ->willReturn([]);

        $transactionManagerMock->expects(self::once())
            ->method('update')
            ->with($orderId, $this->updateRequest)
            ->willReturn($mockResponse);

        $sdk->expects(self::once())
            ->method('getTransactionManager')
            ->willReturn($transactionManagerMock);

        return $sdk;
    }
}
