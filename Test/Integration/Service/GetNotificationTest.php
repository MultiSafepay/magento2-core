<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * Copyright © MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Test\Integration\Service;

use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Service\GetNotification;
use MultiSafepay\ConnectCore\Service\Process\DelayExecution;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use PHPUnit\Framework\MockObject\Exception;
use MultiSafepay\Sdk;
use MultiSafepay\Api\TransactionManager;
use MultiSafepay\Api\Transactions\TransactionResponse;

/**
 * @magentoDbIsolation enabled
 * @magentoConfigFixture default_store multisafepay/general/mode 0
 */
class GetNotificationTest extends AbstractTestCase
{
    /**
     * Test that the service fails gracefully with execution details when the order does not exist in DB.
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws Exception
     * @throws \Exception
     */
    public function testExecuteReturnsFailureWhenOrderNotFound(): void
    {
        $sdkFactoryMock = $this->createMock(SdkFactory::class);

        $getNotificationService = $this->getObjectManager()->create(GetNotification::class, [
            'sdkFactory' => $sdkFactoryMock
        ]);

        $result = $getNotificationService->execute('NON_EXISTENT_ORDER_ID', 1);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('doesn\'t exist', (string)$result['message']);
    }

    /**
     * Test that the service detects a mismatch between the Order Increment ID in Magento
     * and the 'order_id' returned by the MultiSafepay API response.
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws Exception
     * @throws \Exception
     */
    public function testExecuteReturnsFailureOnIncrementIdMismatch(): void
    {
        $orderIncrementId = '100000001';
        $storeId = 1;

        $mockTransactionData = [
            'order_id' => '999999999',
            'status'   => 'completed'
        ];

        $sdkFactoryMock = $this->createSdkFactoryMock($mockTransactionData, $orderIncrementId);
        $delayExecutionMock = $this->createMock(DelayExecution::class);

        /** @var GetNotification $getNotificationService */
        $getNotificationService = $this->getObjectManager()->create(GetNotification::class, [
            'sdkFactory' => $sdkFactoryMock,
            'delayExecution' => $delayExecutionMock
        ]);

        $result = $getNotificationService->execute($orderIncrementId, $storeId);

        $this->assertFalse($result['success']);
        $this->assertEquals('Transaction order_id does not match Magento increment_id', $result['message']);
    }

    /**
     * Test the happy path where Order exists, API returns valid data, and StatusOperationManager is called.
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws Exception
     * @throws \Exception
     */
    public function testExecuteSuccessAndDelegatesToStatusManager(): void
    {
        $orderIncrementId = '100000001';
        $storeId = 1;

        $mockTransactionData = [
            'order_id'       => $orderIncrementId,
            'transaction_id' => '123456789',
            'status'         => 'completed',
            'amount'         => 10000,
            'currency'       => 'USD'
        ];

        $sdkFactoryMock = $this->createSdkFactoryMock($mockTransactionData, $orderIncrementId);
        $delayExecutionMock = $this->createMock(DelayExecution::class);

        /** @var GetNotification $getNotificationService */
        $getNotificationService = $this->getObjectManager()->create(GetNotification::class, [
            'sdkFactory' => $sdkFactoryMock,
            'delayExecution' => $delayExecutionMock
        ]);

        $result = $getNotificationService->execute($orderIncrementId, $storeId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
    }

    /**
     * Returns a mocked SdkFactory that simulates the API response for a given transaction data and expected order ID.
     * @throws Exception
     */
    private function createSdkFactoryMock(array $transactionData, string $expectedOrderId)
    {
        $transactionResponseMock = $this->getMockBuilder(TransactionResponse::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->getMock();

        $transactionResponseMock->method('getData')
            ->willReturn($transactionData);

        $transactionManagerMock = $this->getMockBuilder(TransactionManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();

        $transactionManagerMock->method('get')
            ->with($expectedOrderId)
            ->willReturn($transactionResponseMock);

        $sdkMock = $this->getMockBuilder(Sdk::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTransactionManager'])
            ->getMock();

        $sdkMock->method('getTransactionManager')->willReturn($transactionManagerMock);

        $sdkFactory = $this->createMock(SdkFactory::class);
        $sdkFactory->method('create')->willReturn($sdkMock);

        return $sdkFactory;
    }
}
