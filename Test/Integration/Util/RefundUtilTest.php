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

use Exception;
use Magento\Framework\Exception\LocalizedException;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\RefundUtil;
use MultiSafepay\Exception\InvalidArgumentException;

class RefundUtilTest extends AbstractTestCase
{
    /**
     * @var RefundUtil
     */
    private $refundUtil;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->refundUtil = $this->getObjectManager()->create(RefundUtil::class);
    }

    /**
     * Test if the adjustment item is correctly built
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function testBuildAdjustment()
    {
        $request = ['adjustment' => 3, 'currency' => 'EUR'];
        $result = $this->refundUtil->buildAdjustment($request);

        self::assertStringContainsString('adjustment-', $result->getMerchantItemId());
        self::assertEquals(1, $result->getQuantity());
        self::assertEquals('Adjustment for refund', $result->getName());
        self::assertEquals('Adjustment for refund', $result->getDescription());
        self::assertEquals(-3.0, $result->getUnitPriceValue());
        self::assertEquals(0, $result->getTaxRate());
    }

    /**
     * Test if the shipping item is correctly built
     *
     * @magentoDataFixture   Magento/Customer/_files/customer.php
     * @magentoDataFixture   Magento/Catalog/_files/product_simple.php
     * @magentoDataFixture   Magento/Sales/_files/quote.php
     *
     * @return void
     * @throws LocalizedException
     * @throws Exception
     */
    public function testBuildShipping()
    {
        $this->includeFixtureFile('order_with_tax');
        $this->getOrder();

        $request = ['shipping' => 5, 'currency' => 'EUR', 'order_id' => '100000001'];
        $result = $this->refundUtil->buildShipping($request);

        self::assertStringContainsString('msp-shipping-', $result->getMerchantItemId());
        self::assertEquals(1, $result->getQuantity());
        self::assertEquals('Refund for shipping', $result->getName());
        self::assertEquals('Refund for shipping', $result->getDescription());
        self::assertEquals(-5.0, $result->getUnitPriceValue());
        self::assertEquals(0, $result->getTaxRate());
    }
}
