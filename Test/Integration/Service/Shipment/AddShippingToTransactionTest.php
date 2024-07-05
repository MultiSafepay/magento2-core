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
use MultiSafepay\Api\Base\Response;
use MultiSafepay\Api\TransactionManager;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\Exception\ApiUnavailableException;
use MultiSafepay\Sdk;
use MultiSafepay\ConnectCore\Service\Shipment\AddShippingToTransaction;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use MultiSafepay\Api\Transactions\UpdateRequest;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\ShipmentUtil;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddShippingToTransactionTest extends AbstractTestCase
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SdkFactory
     */
    private $sdkFactory;

    /**
     * @var AddShippingToTransaction
     */
    private $addShippingToTransaction;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $updateRequest = $this->getObjectManager()->get(UpdateRequest::class);
        $shipmentUtil = $this->getObjectManager()->get(ShipmentUtil::class);
        $messageManager = $this->getObjectManager()->get(ManagerInterface::class);
        $this->sdkFactory = $this->createMock(SdkFactory::class);

        $this->addShippingToTransaction = new AddShippingToTransaction(
            $this->logger,
            $updateRequest,
            $shipmentUtil,
            $messageManager,
            $this->sdkFactory
        );
    }

    /**
     * Test if the execute function is successful
     *
     * @throws ClientExceptionInterface
     * @throws ApiException
     * @throws ApiUnavailableException
     * @throws Exception
     */
    public function testExecuteWillLogInfoForOrderOnSuccess()
    {
        $testData = $this->prepareTestData();

        $shipment = $testData['shipment'];
        $order = $testData['order'];
        $transactionManager = $testData['transactionManager'];

        $response = new Response(['data' => ['success' => true]]);
        $transactionManager->method('update')->willReturn($response);

        $this->logger->expects($this->once())
            ->method('logInfoForOrder')
            ->with(
                $this->equalTo('100000001'),
                $this->equalTo('The shipping status has been updated at MultiSafepay')
            );

        $this->addShippingToTransaction->execute($shipment, $order);
    }

    /**
     * Test if the execute function will log an error message when an ApiException is thrown
     *
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function testExecuteWillLogApiExceptionWhenThrown()
    {
        $testData = $this->prepareTestData();

        $shipment = $testData['shipment'];
        $order = $testData['order'];
        $transactionManager = $testData['transactionManager'];

        $exception = new ApiException('ApiException Error', 404);
        $transactionManager->method('update')->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('logUpdateRequestApiException')
            ->with($this->equalTo('100000001'), $this->equalTo($exception));

        $this->addShippingToTransaction->execute($shipment, $order);
    }

    /**
     * @return array
     */
    private function prepareTestData(): array
    {
        $shipment = $this->createMock(ShipmentInterface::class);
        $order = $this->createMock(OrderInterface::class);
        $order->method('getIncrementId')->willReturn('100000001');
        $sdk = $this->createMock(Sdk::class);
        $this->sdkFactory->method('create')->willReturn($sdk);
        $transactionManager = $this->createMock(TransactionManager::class);
        $sdk->method('getTransactionManager')->willReturn($transactionManager);

        return ['shipment' => $shipment, 'order' => $order, 'transactionManager' => $transactionManager];
    }
}
