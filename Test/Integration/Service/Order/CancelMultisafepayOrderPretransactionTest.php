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

namespace MultiSafepay\ConnectCore\Test\Integration\Service\Order;

use Magento\Framework\Exception\LocalizedException;
use MultiSafepay\Api\Base\Response;
use MultiSafepay\Api\TransactionManager;
use MultiSafepay\Api\Transactions\UpdateRequest;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\Order\CancelMultisafepayOrderPretransaction;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\Sdk;
use PHPUnit\Framework\MockObject\MockObject;

class CancelMultisafepayOrderPretransactionTest extends AbstractTestCase
{
    /**
     * @var CancelMultisafepayOrderPretransaction
     */
    private $cancelMultisafepayOrderPretransaction;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->cancelMultisafepayOrderPretransaction =
            $this->getObjectManager()->get(CancelMultisafepayOrderPretransaction::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @throws LocalizedException
     */
    public function testCancelMultisafepayOrderPretransactionSuccess(): void
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $cancelMultisafepayOrderPretransactionMock =
            $this->getMockBuilder(CancelMultisafepayOrderPretransaction::class)->setConstructorArgs([
                $this->getObjectManager()->get(UpdateRequest::class),
                $this->getObjectManager()->get(Logger::class),
                $this->setupSdkFactory($this->getSdkMock($order->getIncrementId())),
            ])
                ->setMethodsExcept(['execute'])
                ->getMock();

        self::assertTrue($cancelMultisafepayOrderPretransactionMock->execute($order));
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @throws LocalizedException
     */
    public function testCancelMultisafepayOrderPretransactionFailed(): void
    {
        self::assertFalse(
            $this->cancelMultisafepayOrderPretransaction->execute($this->getOrderWithVisaPaymentMethod())
        );
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
            ->with($orderId, $this->getObjectManager()->get(UpdateRequest::class))
            ->willReturn($mockResponse);

        $sdk->expects(self::once())
            ->method('getTransactionManager')
            ->willReturn($transactionManagerMock);

        return $sdk;
    }
}
