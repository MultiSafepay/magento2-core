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
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\AmountUtil;

class AmountUtilTest extends AbstractTestCase
{
    /**
     * @var AmountUtil
     */
    private $amountUtil;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->amountUtil = $this->getObjectManager()->create(AmountUtil::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/use_base_currency 1
     * @throws LocalizedException
     */
    public function testGetAmountWithBaseCurrencySetting(): void
    {
        $order = $this->getOrder()->setBaseToOrderRate(0.2);

        self::assertEquals(
            (float)$order->getBaseGrandTotal(),
            $this->amountUtil->getAmount((float)$order->getBaseGrandTotal(), $order)
        );
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/use_base_currency 0
     * @throws LocalizedException
     */
    public function testGetAmountWithoutBaseCurrencySetting(): void
    {
        $order = $this->getOrder()->setBaseToOrderRate(0.2);

        self::assertNotEquals(
            (float)$order->getBaseGrandTotal(),
            $this->amountUtil->getAmount((float)$order->getBaseGrandTotal(), $order)
        );
    }
}
