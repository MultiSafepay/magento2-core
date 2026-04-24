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
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Tax\Model\Config;
use MultiSafepay\ConnectCore\Config\Config as MultiSafepayConfig;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\PriceUtil;
use MultiSafepay\ConnectCore\Util\TaxUtil;

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
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/use_base_currency 1
     * @throws LocalizedException
     */
    public function testGetGrandTotal(): void
    {
        $order = $this->getOrder();

        self::assertSame((float)$order->getBaseGrandTotal(), $this->priceUtil->getGrandTotal($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_shipping_and_invoice.php
     * @magentoDataFixture Magento/Sales/_files/quote_with_multiple_products.php
     * @magentoConfigFixture default_store multisafepay/general/use_base_currency 0
     * @throws LocalizedException
     * @throws Exception
     */
    public function testGetShippingUnitPrice(): void
    {
        $order = $this->getOrder();
        $quote = $this->getQuote('tableRate');
        $order->setQuoteId($quote->getId())->setShippingInclTax(10);

        self::assertSame(10.0, $this->getPriceUtilWithShippingTaxRate(0)->getShippingUnitPrice($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_shipping_and_invoice.php
     * @magentoDataFixture Magento/Sales/_files/quote_with_multiple_products.php
     * @magentoConfigFixture default_store multisafepay/general/use_base_currency 0
     * @throws LocalizedException
     * @throws Exception
     */
    public function testGetShippingUnitPriceWithBaseShippingAmount(): void
    {
        $order = $this->getOrder();
        $quote = $this->getQuote('tableRate');
        $order->setQuoteId($quote->getId())->setShippingInclTax(10)->setBaseShippingAmount(10);

        self::assertSame(10.0, $this->getPriceUtilWithShippingTaxRate(0)->getShippingUnitPrice($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_shipping_and_invoice.php
     * @magentoDataFixture Magento/Sales/_files/quote_with_multiple_products.php
     * @magentoConfigFixture default_store multisafepay/general/use_base_currency 1
     * @throws LocalizedException
     * @throws Exception
     */
    public function testGetBaseShippingUnitPriceWithTax(): void
    {
        $order = $this->getOrder();
        $quote = $this->getQuote('tableRate');
        $order->setQuoteId($quote->getId())->setBaseShippingInclTax(10);

        self::assertEquals(
            8.264462809917356,
            $this->getPriceUtilWithShippingTaxRate(21)->getShippingUnitPrice($order)
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_tax.php
     * @magentoConfigFixture default_store multisafepay/general/use_base_currency 1
     * @throws LocalizedException
     */
    public function testGetUnitRowItemPriceWithTax(): void
    {
        $order = $this->getOrder();
        $orderItems = $order->getItems();
        $firstItem = current($orderItems);
        $firstItem->setBaseRowTotalInclTax($firstItem->getBasePrice() * $firstItem->getQtyOrdered());

        self::assertSame(
            10.0,
            $this->priceUtil->getUnitRowItemPriceWithTax($firstItem, $order->getStoreId())
        );
    }

    /**
     * Sales display excludes tax, so fallback should use the catalog price setting.
     * Catalog prices include tax => incl-tax calculation should be used.
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testGetBaseUnitPriceWithCatalogPriceIncludeTaxSetting(): void
    {
        $orderItem = $this->createOrderItemForConfig([
            Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX => 1,
            Config::CONFIG_XML_PATH_DISCOUNT_TAX => 1,
            Config::CONFIG_XML_PATH_APPLY_AFTER_DISCOUNT => 1,
            Config::XML_PATH_DISPLAY_SALES_PRICE => Config::DISPLAY_TYPE_EXCLUDING_TAX,
            sprintf(
                MultiSafepayConfig::DEFAULT_PATH_PATTERN,
                MultiSafepayConfig::USE_BASE_CURRENCY
            ) => 1,
        ]);

        self::assertEquals(
            $this->getExpectedBaseUnitPriceInclTax($orderItem),
            $this->priceUtil->getUnitPrice($orderItem, (int)$orderItem->getStoreId())
        );
    }

    /**
     * Sales display excludes tax, so fallback should use the catalog price setting.
     * Catalog prices exclude tax => excl-tax calculation should be used.
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testGetBaseUnitPriceWithCatalogPriceExcludedTaxSetting(): void
    {
        $orderItem = $this->createOrderItemForConfig([
            Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX => 0,
            Config::CONFIG_XML_PATH_DISCOUNT_TAX => 0,
            Config::CONFIG_XML_PATH_APPLY_AFTER_DISCOUNT => 1,
            Config::XML_PATH_DISPLAY_SALES_PRICE => Config::DISPLAY_TYPE_EXCLUDING_TAX,
            sprintf(
                MultiSafepayConfig::DEFAULT_PATH_PATTERN,
                MultiSafepayConfig::USE_BASE_CURRENCY
            ) => 1,
        ]);

        self::assertEquals(
            $this->getExpectedBaseUnitPriceExclTax($orderItem),
            $this->priceUtil->getUnitPrice($orderItem, (int)$orderItem->getStoreId())
        );
    }

    /**
     * Regression test for:
     * Catalog Prices = Excluding Tax
     * Apply Discount On Prices = Including Tax
     * Orders, Invoices, Credit Memos Display Settings = Including Tax
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testGetUnitPriceUsesInclTaxWhenSalesDisplayPricesIncludeTax(): void
    {
        $orderItem = $this->createOrderItemForConfig([
            Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX => 0,
            Config::CONFIG_XML_PATH_DISCOUNT_TAX => 1,
            Config::CONFIG_XML_PATH_APPLY_AFTER_DISCOUNT => 0,
            Config::XML_PATH_DISPLAY_SALES_PRICE => Config::DISPLAY_TYPE_INCLUDING_TAX,
            sprintf(
                MultiSafepayConfig::DEFAULT_PATH_PATTERN,
                MultiSafepayConfig::USE_BASE_CURRENCY
            ) => 1,
        ]);

        self::assertEquals(
            $this->getExpectedBaseUnitPriceInclTax($orderItem),
            $this->priceUtil->getUnitPrice($orderItem, (int)$orderItem->getStoreId())
        );
    }

    /**
     * Displaying both should still prefer incl-tax amounts.
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testGetUnitPriceUsesInclTaxWhenSalesDisplayPricesAreBoth(): void
    {
        $orderItem = $this->createOrderItemForConfig([
            Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX => 0,
            Config::CONFIG_XML_PATH_DISCOUNT_TAX => 1,
            Config::CONFIG_XML_PATH_APPLY_AFTER_DISCOUNT => 0,
            Config::XML_PATH_DISPLAY_SALES_PRICE => Config::DISPLAY_TYPE_BOTH,
            sprintf(
                MultiSafepayConfig::DEFAULT_PATH_PATTERN,
                MultiSafepayConfig::USE_BASE_CURRENCY
            ) => 1,
        ]);

        self::assertEquals(
            $this->getExpectedBaseUnitPriceInclTax($orderItem),
            $this->priceUtil->getUnitPrice($orderItem, (int)$orderItem->getStoreId())
        );
    }

    private function createOrderItemForConfig(array $configOverrides): OrderItemInterface
    {
        $quote = $this->getQuoteWithTaxesAndDiscount([
            'config_data' => [
                'config_overrides' => $configOverrides,
            ],
        ]);

        $quoteItem = $quote->getAllItems()[0];
        $orderItem = $this->createOrderItemFromQuoteItem($quoteItem);
        $orderItem->setStoreId($quote->getStoreId());

        return $orderItem;
    }

    /**
     * @param CartItemInterface $quoteItem
     * @return OrderItemInterface
     */
    private function createOrderItemFromQuoteItem(CartItemInterface $quoteItem): OrderItemInterface
    {
        return $this->getObjectManager()
            ->create(OrderItemInterface::class)
            ->setData($quoteItem->getData())
            ->setQtyOrdered($quoteItem->getQty());
    }

    /**
     * Get the expected base unit price including tax
     *
     * @param OrderItemInterface $item
     * @return float
     */
    private function getExpectedBaseUnitPriceInclTax(OrderItemInterface $item): float
    {
        $quantity = (float)$item->getQtyOrdered();
        $taxMultiplier = 1 + ((float)$item->getTaxPercent() / 100);

        return (($item->getBaseRowTotalInclTax() - $item->getBaseDiscountAmount()) / $quantity) / $taxMultiplier;
    }

    /**
     * Get the expected base unit price excluding tax
     *
     * @param OrderItemInterface $item
     * @return float
     */
    private function getExpectedBaseUnitPriceExclTax(OrderItemInterface $item): float
    {
        $quantity = (float)$item->getQtyOrdered();

        return ($item->getBasePrice() - ($item->getBaseDiscountAmount() / $quantity))
            + ($item->getBaseDiscountTaxCompensationAmount() / $quantity);
    }

    /**
     * Get a PriceUtil instance with a mocked TaxUtil that returns the specified tax rate for shipping.
     *
     * @param float $taxRate
     * @return PriceUtil
     */
    private function getPriceUtilWithShippingTaxRate(float $taxRate): PriceUtil
    {
        $taxUtil = $this->getMockBuilder(TaxUtil::class)
            ->disableOriginalConstructor()
            ->getMock();

        $taxUtil->method('getShippingTaxRate')->willReturn($taxRate);

        return new PriceUtil(
            $this->getObjectManager()->get(MultiSafepayConfig::class),
            $this->getObjectManager()->get(ScopeConfigInterface::class),
            $taxUtil
        );
    }
}
