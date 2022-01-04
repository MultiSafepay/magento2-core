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
 * Copyright © 2020 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\Test\Integration\Util;

use Magento\Framework\Exception\LocalizedException;
use MultiSafepay\Api\Transactions\Transaction as TransactionStatus;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\OrderStatusUtil;

class OrderStatusUtilTest extends AbstractTestCase
{
    /**
     * @var OrderStatusUtil
     */
    private $orderStatusUtil;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->orderStatusUtil = $this->getObjectManager()->create(OrderStatusUtil::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/order_status pending
     * @throws LocalizedException
     */
    public function testGetPendingStatusWithPreselected(): void
    {
        self::assertEquals('pending', $this->orderStatusUtil->getPendingStatus($this->getOrder()));
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/order_status
     * @throws LocalizedException
     */
    public function testGetPendingStatusWithoutPreselected(): void
    {
        self::assertEquals('pending', $this->orderStatusUtil->getPendingStatus($this->getOrder()));
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/pending_payment_order_status pending_payment
     * @throws LocalizedException
     */
    public function testGetPendingPaymentStatusWithPreselected(): void
    {
        self::assertEquals(
            'pending_payment',
            $this->orderStatusUtil->getPendingPaymentStatus($this->getOrder())
        );
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/pending_payment_order_status
     * @throws LocalizedException
     */
    public function testGetPendingPaymentStatusWithoutPreselected(): void
    {
        self::assertNotEquals(
            'pending',
            $this->orderStatusUtil->getPendingPaymentStatus($this->getOrder())
        );
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/processing_order_status processing
     * @throws LocalizedException
     */
    public function testGetProcessingStatusWithPreselected(): void
    {
        self::assertEquals('processing', $this->orderStatusUtil->getProcessingStatus($this->getOrder()));
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/processing_order_status
     * @throws LocalizedException
     */
    public function testGetProcessingStatusWithoutPreselected(): void
    {
        self::assertNotEquals('fraud', $this->orderStatusUtil->getProcessingStatus($this->getOrder()));
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/status/initialized_status pending
     * @throws LocalizedException
     */
    public function testGetOrderStatusForInitializedMultisafepayStatus(): void
    {
        self::assertEquals(
            'pending',
            $this->orderStatusUtil->getOrderStatusByTransactionStatus($this->getOrder(), TransactionStatus::INITIALIZED)
        );
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/status/reserved_status pending
     * @throws LocalizedException
     */
    public function testGetOrderStatusForReservedMultisafepayStatus(): void
    {
        self::assertEquals(
            'pending',
            $this->orderStatusUtil->getOrderStatusByTransactionStatus($this->getOrder(), TransactionStatus::RESERVED)
        );
    }
}
