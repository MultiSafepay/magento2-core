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

use Magento\Framework\Exception\NoSuchEntityException;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\OrderUtil;

class OrderUtilTest extends AbstractTestCase
{
    /**
     * @var OrderUtil
     */
    private $orderUtil;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->orderUtil = $this->getObjectManager()->create(OrderUtil::class);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     *
     * @throws NoSuchEntityException
     */
    public function testGetOrderByIncrementId(): void
    {
        $incrementId = '100000001';
        $order = $this->orderUtil->getOrderByIncrementId($incrementId);

        self::assertEquals($incrementId, $order->getRealOrderId());
        $this->expectException(NoSuchEntityException::class);

        $this->orderUtil->getOrderByIncrementId($incrementId . '_test_fail');
    }
}
