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

namespace MultiSafepay\ConnectCore\Test\Integration\Gateway\Http\Client;

use Exception;
use MultiSafepay\Api\Base\Response;
use MultiSafepay\Api\TransactionManager;
use MultiSafepay\Api\Transactions\CaptureRequest;
use MultiSafepay\Api\Transactions\Transaction;
use MultiSafepay\ConnectCore\Gateway\Http\Client\CancelClient;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Test\Integration\Gateway\AbstractGatewayTestCase;
use MultiSafepay\Sdk;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CancelClientTest extends AbstractGatewayTestCase
{
    /**
     * @var CaptureRequest
     */
    private $captureRequest;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->captureRequest = $this->getObjectManager()->get(CaptureRequest::class);
    }

    /**
     * @throws Exception
     * @throws ClientExceptionInterface
     */
    public function testSuccessCancelBuildForPartialCaptureTransaction(): void
    {
        $fakeOrderIncrementId = '1000010010';
        $captureRequestPayload = $this->captureRequest->addData(
            [
                "status" => Transaction::CANCELLED,
                "reason" => "Order cancelled",
            ]
        );

        $cancelClientMock = $this->getMockBuilder(CancelClient::class)
            ->setConstructorArgs([
                $this->setupSdkFactory(
                    $this->getSdkMockWithCaptureReservationCancel($fakeOrderIncrementId, $captureRequestPayload)
                ),
                $this->getObjectManager()->get(Logger::class)
            ])
            ->setMethodsExcept(['placeRequest'])
            ->getMock();

        self::assertNull($cancelClientMock->placeRequest($this->prepareTransferObjectMock([])));
        $cancelResult = $cancelClientMock->placeRequest(
            $this->prepareTransferObjectMock(
                [
                    'order_id' => $fakeOrderIncrementId,
                    'payload' => $captureRequestPayload,
                ]
            )
        );
        self::isTrue(isset($cancelResult['order_id']));
        self::isTrue(isset($cancelResult['payload']));
    }

    /**
     * @param string $orderId
     * @param CaptureRequest $captureRequestPayload
     * @return MockObject
     */
    protected function getSdkMockWithCaptureReservationCancel(
        string $orderId,
        CaptureRequest $captureRequestPayload
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

        $mockResponse->expects(self::any())
            ->method('getResponseData')
            ->willReturn([]);

        $transactionManagerMock->expects(self::any())
            ->method('captureReservationCancel')
            ->with($orderId, $captureRequestPayload)
            ->willReturn($mockResponse);

        $sdk->expects(self::any())
            ->method('getTransactionManager')
            ->willReturn($transactionManagerMock);

        return $sdk;
    }
}
