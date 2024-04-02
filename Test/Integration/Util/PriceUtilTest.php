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
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Pricing\PriceCurrencyInterface as PriceRounder;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Tax\Model\Config;
use MultiSafepay\ConnectCore\Config\Config as MultiSafepayConfig;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\PriceUtil;
use MultiSafepay\ConnectCore\Util\TaxUtil;
use PHPUnit\Framework\MockObject\MockObject;

class PriceUtilTest extends AbstractTestCase
{
    /**
     * @var PriceUtil
     */
    private $priceUtil;

    protected function setUp(): void
    {
        $this->priceUtil = $this->getObjectManager()->create(PriceUtil::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/use_base_currency 1
     * @throws LocalizedException
     */
    public function testGetGrandTotal(): void
    {
        $order = $this->getOrder();

        self::assertEquals((float)$order->getBaseGrandTotal(), $this->priceUtil->getGrandTotal($order));
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order_with_shipping_and_invoice.php
     * @magentoDataFixture   Magento/Sales/_files/quote_with_multiple_products.php
     * @magentoConfigFixture default_store multisafepay/general/use_base_currency 0
     * @throws LocalizedException
     * @throws Exception
     */
    public function testGetShippingUnitPrice(): void
    {
        $order = $this->getOrder();
        $quote = $this->getQuote('tableRate');
        $order->setQuoteId($quote->getId())->setShippingInclTax(10);

        self::assertEquals((float)10, $this->getPriceUtilMock(0)->getShippingUnitPrice($order));
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order_with_shipping_and_invoice.php
     * @magentoDataFixture   Magento/Sales/_files/quote_with_multiple_products.php
     * @magentoConfigFixture default_store multisafepay/general/use_base_currency 0
     * @throws LocalizedException
     * @throws Exception
     */
    public function testGetShippingUnitPriceWithBaseShippingAmount(): void
    {
        $order = $this->getOrder();
        $quote = $this->getQuote('tableRate');
        $order->setQuoteId($quote->getId())->setShippingInclTax(10)->setBaseShippingAmount(10);

        self::assertEquals(10.0, $this->getPriceUtilMock(0)->getShippingUnitPrice($order));
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order_with_shipping_and_invoice.php
     * @magentoDataFixture   Magento/Sales/_files/quote_with_multiple_products.php
     * @magentoConfigFixture default_store multisafepay/general/use_base_currency 1
     * @throws LocalizedException
     * @throws Exception
     */
    public function testGetBaseShippingUnitPriceWithTax(): void
    {
        $order = $this->getOrder();
        $quote = $this->getQuote('tableRate');
        $order->setQuoteId($quote->getId())->setBaseShippingInclTax(10);

        self::assertEquals(8.264462809917356, $this->getPriceUtilMock(21)->getShippingUnitPrice($order));
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order_with_tax.php
     * @magentoConfigFixture default_store multisafepay/general/use_base_currency 1
     * @throws LocalizedException
     */
    public function testGetUnitRowItemPriceWithTax(): void
    {
        $order = $this->getOrder();
        $orderItems = $order->getItems();
        $firstItem = current($orderItems);
        $firstItem->setBaseRowTotalInclTax($firstItem->getBasePrice() * $firstItem->getQtyOrdered());

        self::assertEquals(
            (float)10,
            $this->priceUtil->getUnitRowItemPriceWithTax($firstItem, $order->getStoreId())
        );
    }

    /**
     * @magentoDbIsolation     enabled
     * @magentoAppIsolation    enabled
     */
    public function testGetBaseUnitPriceWithCatalogPriceIncludeTaxSetting(): void
    {
        $this->checkCalculationEquals([
            'config_data' => [
                'config_overrides' => [
                    Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX => 1,
                    Config::CONFIG_XML_PATH_DISCOUNT_TAX => 1,
                    Config::CONFIG_XML_PATH_APPLY_AFTER_DISCOUNT => 1,
                    sprintf(
                        MultiSafepayConfig::DEFAULT_PATH_PATTERN,
                        MultiSafepayConfig::USE_BASE_CURRENCY
                    ) => 1,
                ],
            ],
        ]);
    }

    /**
     * @magentoDbIsolation     enabled
     * @magentoAppIsolation    enabled
     */
    public function testGetBaseUnitPriceWithCatalogPriceExcludedTaxSetting(): void
    {
        $this->checkCalculationEquals([
            'config_data' => [
                'config_overrides' => [
                    Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX => 0,
                    Config::CONFIG_XML_PATH_DISCOUNT_TAX => 0,
                    Config::CONFIG_XML_PATH_APPLY_AFTER_DISCOUNT => 1,
                    sprintf(
                        MultiSafepayConfig::DEFAULT_PATH_PATTERN,
                        MultiSafepayConfig::USE_BASE_CURRENCY
                    ) => 1,
                ],
            ],
        ]);
    }

    /**
     * @param array $additionalTaxConfigs
     */
    private function checkCalculationEquals(array $additionalTaxConfigs = []): void
    {
        $objectManager = $this->getObjectManager();
        $priceRounder = $objectManager->create(PriceRounder::class);
        $quote = $this->getQuoteWithTaxesAndDiscount($additionalTaxConfigs);
        $quoteItem = $quote->getAllItems()[0];
        $orderItem = $objectManager->create(OrderItemInterface::class)->setData($quoteItem->getData())
            ->setQtyOrdered($quoteItem->getQty());
        $unitPrice = $this->priceUtil->getUnitPrice($orderItem, $quote->getStoreId());

        self::assertEquals(
            $priceRounder->roundPrice($quote->getBaseGrandTotal()),
            $priceRounder->roundPrice(
                $unitPrice * $quote->getItemsQty() + $quoteItem->getBaseTaxAmount() + $quote->getBaseShippingAmount()
            )
        );
    }

    /**
     * @param float $taxRate
     * @return PriceUtil
     */
    private function getPriceUtilMock(float $taxRate): PriceUtil
    {
        $taxUtil = $this->getMockBuilder(TaxUtil::class)->disableOriginalConstructor()->getMock();
        $taxUtil->method('getShippingTaxRate')->willReturn($taxRate);

        return $this->getMockBuilder(PriceUtil::class)
            ->setConstructorArgs([
                $this->getObjectManager()->get(MultiSafepayConfig::class),
                $this->getObjectManager()->get(ScopeConfigInterface::class),
                $taxUtil
            ])->setMethodsExcept(['getShippingUnitPrice'])
            ->getMock();
    }
}
