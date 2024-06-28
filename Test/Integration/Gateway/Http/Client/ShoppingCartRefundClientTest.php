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
use Magento\Payment\Gateway\Http\TransferInterface;
use MultiSafepay\Api\Base\Response;
use MultiSafepay\Api\TransactionManager;
use MultiSafepay\Api\Transactions\RefundRequest;
use MultiSafepay\Api\Transactions\RefundRequest\Arguments\CheckoutData;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\ConnectCore\Gateway\Http\Client\ShoppingCartRefundClient;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\JsonHandler;
use MultiSafepay\ConnectCore\Util\RefundUtil;
use MultiSafepay\Sdk;
use MultiSafepay\ValueObject\CartItem;
use MultiSafepay\ValueObject\Money;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ShoppingCartRefundClientTest extends AbstractTestCase
{
    public const FAKE_TRANSACTION_ID = '11112222';
    public const FAKE_REFUND_ID = '11223344';

    /**
     * Test if the ShoppingCartRefundClient executes with given data
     *
     * @throws Exception
     */
    public function testShoppingCartRefundClient(): void
    {
        $refundRequestPayload = $this->getRefundRequest()
            ->addMoney(new Money(100, 'USD'))
            ->addCheckoutData(
                (new CheckoutData())->addItems(
                    [
                        (new CartItem())::fromData([
                            'name' => 'Simple Product',
                            'unit_price' => 100,
                            'currency' => 'USD',
                            'tax_rate' => null,
                            'quantity' => 2,
                            'merchant_item_id' => 'simple',
                            'tax_table_selector' => '0',
                            'description' => '',
                        ]),
                    ]
                )
            );

        $refundClientMock = $this->getMockBuilder(ShoppingCartRefundClient::class)->setConstructorArgs([
            $this->setupSdkFactory($this->getSdkMockWithRefundMethod($refundRequestPayload)),
            $this->getObjectManager()->get(Logger::class),
            $this->getObjectManager()->get(RefundUtil::class),
            $this->getObjectManager()->get(JsonHandler::class)
        ])->setMethodsExcept(['placeRequest'])->getMock();

        /** @var TransferInterface $transferObject */
        $transferObject = $this->prepareTransferObjectMock([
            'order_id' => '1000010010',
            'store_id' => 1,
            'currency' => 'EUR',
            'items' => [
                [
                    'merchant_item_id' => 'simple',
                    'quantity' => 1,
                ],
            ],
            'shipping' => 5,
            'adjustment' => 3,
            'transaction' => $this->getTransactionResponseMock()
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
     * @return MockObject
     */
    private function getTransactionResponseMock(): MockObject
    {
        return $this->getMockBuilder(TransactionResponse::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param RefundRequest $refundRequestPayload
     * @return MockObject
     */
    protected function getSdkMockWithRefundMethod(
        RefundRequest $refundRequestPayload
    ): MockObject {
        $sdk = $this->getMockBuilder(Sdk::class)
            ->disableOriginalConstructor()
            ->getMock();

        $transactionManagerMock = $this->getMockBuilder(TransactionManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $transactionMock = $this->getMockBuilder(TransactionResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $transactionManagerMock->method('get')->willReturn($transactionMock);
        $transactionManagerMock
            ->method('createRefundRequest')
            ->with($transactionMock)
            ->willReturn($refundRequestPayload);

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
            ->with($transactionMock, $refundRequestPayload)
            ->willReturn($mockResponse);

        $sdk->method('getTransactionManager')
            ->willReturn($transactionManagerMock);

        return $sdk;
    }
}
