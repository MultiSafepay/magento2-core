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

use Magento\Payment\Gateway\Http\TransferInterface;
use MultiSafepay\Api\Base\Response;
use MultiSafepay\Api\TransactionManager;
use MultiSafepay\Api\Transactions\RefundRequest;
use MultiSafepay\Api\Transactions\TransactionResponse as Transaction;
use MultiSafepay\ConnectCore\Gateway\Http\Client\RefundClient;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\JsonHandler;
use MultiSafepay\Sdk;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Client\ClientExceptionInterface;

class RefundClientTest extends AbstractTestCase
{
    public const FAKE_TRANSACTION_ID = '11112222';
    public const FAKE_REFUND_ID = '11223344';

    /**
     * @throws ClientExceptionInterface
     */
    public function testRefundClient(): void
    {
        $fakeOrderIncrementId = '1000010010';
        $refundRequestPayload = $this->getRefundRequest()->addData(
            [
                'amount' => 1000,
                'description' => 'test',
            ]
        );

        $refundClientMock = $this->getMockBuilder(RefundClient::class)->setConstructorArgs([
            $this->setupSdkFactory($this->getSdkMockWithRefundMethod($fakeOrderIncrementId, $refundRequestPayload)),
            $this->getObjectManager()->get(Logger::class),
            $this->getObjectManager()->get(JsonHandler::class)
        ])->setMethodsExcept(['placeRequest'])->getMock();

        /** @var TransferInterface $transferObject */
        $transferObject = $this->prepareTransferObjectMock([
            'payload' => $refundRequestPayload,
            'order_id' => $fakeOrderIncrementId,
            'store_id' => 1,
        ]);

        $result = $refundClientMock->placeRequest($transferObject);

        self::assertEquals(self::FAKE_TRANSACTION_ID, $result['transaction_id']);
        self::assertEquals(self::FAKE_REFUND_ID, $result['refund_id']);
    }

    /**
     * @return RefundRequest
     */
    private function getRefundRequest(): RefundRequest
    {
        return $this->getObjectManager()->get(RefundRequest::class);
    }

    /**
     * @param string $orderId
     * @param RefundRequest $refundRequestPayload
     * @return MockObject
     */
    protected function getSdkMockWithRefundMethod(
        string $orderId,
        RefundRequest $refundRequestPayload
    ): MockObject {
        $sdk = $this->getMockBuilder(Sdk::class)
            ->disableOriginalConstructor()
            ->getMock();

        $transactionManagerMock = $this->getMockBuilder(TransactionManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $transactionMock = $this->getMockBuilder((Transaction::class))
            ->disableOriginalConstructor()
            ->getMock();

        $transactionManagerMock->method('get')->willReturn($transactionMock);

        $mockResponse = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockResponse->method('getResponseData')
            ->willReturn(
                [
                    'transaction_id' => self::FAKE_TRANSACTION_ID,
                    'refund_id' => self::FAKE_REFUND_ID,
                ]
            );

        $transactionManagerMock->expects(self::once())->method('refund')
            ->with($transactionMock, $refundRequestPayload, $orderId)
            ->willReturn($mockResponse);

        $sdk->method('getTransactionManager')
            ->willReturn($transactionManagerMock);

        return $sdk;
    }
}
