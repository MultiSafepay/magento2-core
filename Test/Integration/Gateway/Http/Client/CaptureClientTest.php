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
use MultiSafepay\ConnectCore\Gateway\Http\Client\CaptureClient;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Test\Integration\Gateway\AbstractGatewayTestCase;
use MultiSafepay\Sdk;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CaptureClientTest extends AbstractGatewayTestCase
{
    public const FAKE_TRANSACTION_ID = '11112222';

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
     */
    public function testSuccessCancelBuildForPartialCaptureTransaction(): void
    {
        $fakeOrderIncrementId = '1000010010';
        $fakeInvoiceIncrementId = '1000010012';
        $captureRequestPayload = $this->captureRequest->addData(
            [
                "amount" => 10000,
                "invoice_id" => $fakeInvoiceIncrementId,
                "new_order_status" => Transaction::COMPLETED,
                'new_order_id' => $fakeOrderIncrementId . '_' . $fakeInvoiceIncrementId,
            ]
        );

        $captureClientMock = $this->getMockBuilder(CaptureClient::class)
            ->setConstructorArgs([
                $this->setupSdkFactory(
                    $this->getSdkMockWithCaptureMethod($fakeOrderIncrementId, $captureRequestPayload)
                ),
                $this->getObjectManager()->get(Logger::class)
            ])
            ->setMethodsExcept(['placeRequest'])
            ->getMock();

        $result = $captureClientMock->placeRequest(
            $this->prepareTransferObjectMock(
                [
                    'order_id' => $fakeOrderIncrementId,
                    'payload' => $captureRequestPayload,
                ]
            )
        );

        self::assertEquals(self::FAKE_TRANSACTION_ID, $result['transaction_id']);
        self::assertEquals($fakeOrderIncrementId, $result['order_id']);
    }

    /**
     * @param string $orderId
     * @param CaptureRequest $captureRequestPayload
     * @return MockObject
     */
    protected function getSdkMockWithCaptureMethod(
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
            ->willReturn(
                [
                    'transaction_id' => self::FAKE_TRANSACTION_ID,
                    'order_id' => $orderId,
                ]
            );

        $transactionManagerMock->expects(self::any())
            ->method('capture')
            ->with($orderId, $captureRequestPayload)
            ->willReturn($mockResponse);

        $sdk->expects(self::any())
            ->method('getTransactionManager')
            ->willReturn($transactionManagerMock);

        return $sdk;
    }
}
