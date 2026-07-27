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
        $firstItem->setBaseRowTotal($firstItem->getBasePrice() * $firstItem->getQtyOrdered());
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
     * When catalog prices exclude tax, row_total_incl_tax is stored without
     * the discount, so getUnitPrice must use the excl-tax formula to reconcile.
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

        self::assertEqualsWithDelta(
            $this->getExpectedBaseUnitPriceExclTax($orderItem),
            $this->priceUtil->getUnitPrice($orderItem, (int)$orderItem->getStoreId()),
            0.01
        );
    }

    /**
     * Displaying both should still prefer incl-tax amounts.
     * For excl-tax catalog prices the result equals the excl-tax formula.
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

        self::assertEqualsWithDelta(
            $this->getExpectedBaseUnitPriceExclTax($orderItem),
            $this->priceUtil->getUnitPrice($orderItem, (int)$orderItem->getStoreId()),
            0.01
        );
    }

    /**
     * Verify the excl-tax branch formula reconciles:
     * unit_price * qty == row_total - discount_amount + discount_tax_compensation_amount
     *
     * Config: catalog excl tax, discount on excl tax, apply after discount, display excl tax.
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testExclTaxUnitPriceReconcilesWithRowTotal(): void
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

        $qty = (float)$orderItem->getQtyOrdered();
        $expected = $orderItem->getBaseRowTotal() - $orderItem->getBaseDiscountAmount()
                    + $orderItem->getBaseDiscountTaxCompensationAmount();
        $actual = $this->priceUtil->getUnitPriceExclTax($orderItem, (int)$orderItem->getStoreId(), $qty) * $qty;

        self::assertEqualsWithDelta($expected, $actual, 0.01);
    }

    /**
     * Verify the incl-tax branch formula reconciles:
     * unit_price * qty * (1 + taxRate) == row_total_incl_tax - discount_amount
     *
     * Config: catalog incl tax, discount on incl tax, apply after discount, display incl tax.
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testInclTaxUnitPriceReconcilesWithRowTotal(): void
    {
        $orderItem = $this->createOrderItemForConfig([
            Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX => 1,
            Config::CONFIG_XML_PATH_DISCOUNT_TAX => 1,
            Config::CONFIG_XML_PATH_APPLY_AFTER_DISCOUNT => 1,
            Config::XML_PATH_DISPLAY_SALES_PRICE => Config::DISPLAY_TYPE_INCLUDING_TAX,
            sprintf(
                MultiSafepayConfig::DEFAULT_PATH_PATTERN,
                MultiSafepayConfig::USE_BASE_CURRENCY
            ) => 1,
        ]);

        $qty = (float)$orderItem->getQtyOrdered();
        $taxMultiplier = 1 + ((float)$orderItem->getTaxPercent() / 100);
        $expected = $orderItem->getBaseRowTotalInclTax() - $orderItem->getBaseDiscountAmount();
        $actual = $this->priceUtil->getUnitPriceInclTax($orderItem, (int)$orderItem->getStoreId(), $qty)
                  * $qty * $taxMultiplier;

        self::assertEqualsWithDelta($expected, $actual, 0.01);
    }

    /**
     * Verify the incl-tax branch formula reconciles when catalog prices exclude tax
     * but the sales display shows prices including tax.
     *
     * For excl-tax catalog prices Magento stores row_total_incl_tax = row_total × (1+tax)
     * without discount, so the correct reconciliation is:
     *   unit_price × qty × (1+tax) == row_total_incl_tax - discount_amount × (1+tax)
     *                               == (row_total - discount_amount) × (1+tax)
     *
     * Config: catalog excl tax, discount on excl tax, apply after discount, display incl tax.
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testInclTaxBranchWithCatalogPriceExclTaxReconcilesWithRowTotal(): void
    {
        $orderItem = $this->createOrderItemForConfig([
            Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX => 0,
            Config::CONFIG_XML_PATH_DISCOUNT_TAX => 0,
            Config::CONFIG_XML_PATH_APPLY_AFTER_DISCOUNT => 1,
            Config::XML_PATH_DISPLAY_SALES_PRICE => Config::DISPLAY_TYPE_INCLUDING_TAX,
            sprintf(
                MultiSafepayConfig::DEFAULT_PATH_PATTERN,
                MultiSafepayConfig::USE_BASE_CURRENCY
            ) => 1,
        ]);

        $qty = (float)$orderItem->getQtyOrdered();
        $taxMultiplier = 1 + ((float)$orderItem->getTaxPercent() / 100);
        // Correct reconciliation: item contribution incl tax = (row_total - discount) × (1+tax)
        $expected = $orderItem->getBaseRowTotalInclTax()
                    - $orderItem->getBaseDiscountAmount() * $taxMultiplier;
        $actual = $this->priceUtil->getUnitPriceInclTax($orderItem, (int)$orderItem->getStoreId(), $qty)
                  * $qty * $taxMultiplier;

        self::assertEqualsWithDelta($expected, $actual, 0.01);
    }

    /**
     * Verify that division-by-zero is safely guarded: all unit price methods return 0.0
     * when qty is zero.
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testGetUnitPriceReturnsZeroWhenQtyIsZero(): void
    {
        $orderItem = $this->createOrderItemForConfig([
            Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX => 1,
            Config::CONFIG_XML_PATH_DISCOUNT_TAX => 1,
            Config::CONFIG_XML_PATH_APPLY_AFTER_DISCOUNT => 1,
            Config::XML_PATH_DISPLAY_SALES_PRICE => Config::DISPLAY_TYPE_INCLUDING_TAX,
            sprintf(
                MultiSafepayConfig::DEFAULT_PATH_PATTERN,
                MultiSafepayConfig::USE_BASE_CURRENCY
            ) => 1,
        ]);

        $orderItem->setQtyOrdered(0);
        $storeId = (int)$orderItem->getStoreId();

        self::assertSame(0.0, $this->priceUtil->getUnitPrice($orderItem, $storeId));
        self::assertSame(0.0, $this->priceUtil->getUnitPriceExclTax($orderItem, $storeId, 0.0));
        self::assertSame(0.0, $this->priceUtil->getUnitPriceInclTax($orderItem, $storeId, 0.0));
        self::assertSame(0.0, $this->priceUtil->getUnitRowItemPriceWithTax($orderItem, $storeId));
    }

    /**
     * excl-tax catalog prices, excl-tax discount, qty 4, 21% tax, sales display including tax.
     *
     * When price_includes_tax=0, Magento stores row_total_incl_tax = row_total × (1+tax)
     * without the discount applied. The incl-tax branch must scale the discount by
     * (1+tax) to produce the correct excl-tax unit price.
     *
     * row_total = 4 × 45.00 = 180.00 (excl)
     * row_total_incl_tax = 180.00 × 1.21 = 217.80 (no discount)
     * discount_amount = 45.00 (excl)
     * unit_price = (217.80 - 45 × 1.21) / 4 / 1.21 = 33.75
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default_store tax/calculation/price_includes_tax 0
     */
    public function testUnitPriceWith21PercentTaxAndQty4ReconcilesWithRowTotal(): void
    {
        $rowTotal        = 180.0;  // 4 items × 45.00 excl-tax
        $rowTotalInclTax = 217.80; // rowTotal × 1.21 (no discount applied by Magento)
        $discountAmount  = 45.0;   // excl-tax cart-rule discount
        $taxPercent      = 21.0;
        $qty             = 4.0;

        $orderItem = $this->getObjectManager()->create(OrderItemInterface::class);
        $orderItem->setQtyOrdered($qty)
            ->setTaxPercent($taxPercent)
            ->setBaseRowTotal($rowTotal)
            ->setBaseRowTotalInclTax($rowTotalInclTax)
            ->setBaseDiscountAmount($discountAmount)
            ->setRowTotal($rowTotal)
            ->setRowTotalInclTax($rowTotalInclTax)
            ->setDiscountAmount($discountAmount)
            ->setStoreId(1);

        $taxMultiplier = 1 + $taxPercent / 100;
        $expectedUnitPrice = ($rowTotalInclTax - $discountAmount * $taxMultiplier) / $qty / $taxMultiplier;

        self::assertEqualsWithDelta(33.75, $expectedUnitPrice, 0.0001);

        $actualUnitPrice = $this->priceUtil->getUnitPriceInclTax($orderItem, (int)$orderItem->getStoreId(), $qty);

        self::assertEqualsWithDelta($expectedUnitPrice, $actualUnitPrice, 0.0001);

        $reconstructed = $actualUnitPrice * $qty * $taxMultiplier;

        self::assertEqualsWithDelta(($rowTotal - $discountAmount) * $taxMultiplier, $reconstructed, 0.0001);
    }

    /**
     * Integration test for the case where catalog prices exclude tax, discount is applied after tax,
     * and sales display shows prices including tax.
     *
     * tax/calculation/price_includes_tax   = 0 (Excluding Tax)
     * tax/calculation/discount_tax         = 0 (Excluding Tax)
     * tax/calculation/apply_after_discount = 1 (After Discount)
     * tax/sales_display/price              = 2 (Including Tax)
     *
     * With this config shouldUseInclTaxAmounts() = true but catalog prices exclude tax,
     * so the incl-tax formula must scale the discount by (1+tax) before reconciling.
     * The test verifies that getUnitPrice() × qty × (1+tax) equals the item's actual
     * incl-tax contribution to the grand total.
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testExclCatalogPriceInclSalesDisplayReconcilesWithGrandTotal(): void
    {
        $orderItem = $this->createOrderItemForConfig([
            Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX => 0,
            Config::CONFIG_XML_PATH_DISCOUNT_TAX => 0,
            Config::CONFIG_XML_PATH_APPLY_AFTER_DISCOUNT => 1,
            Config::XML_PATH_DISPLAY_SALES_PRICE => Config::DISPLAY_TYPE_INCLUDING_TAX,
            sprintf(
                MultiSafepayConfig::DEFAULT_PATH_PATTERN,
                MultiSafepayConfig::USE_BASE_CURRENCY
            ) => 1,
        ]);

        $qty = (float)$orderItem->getQtyOrdered();
        $taxMultiplier = 1 + ((float)$orderItem->getTaxPercent() / 100);

        $unitPrice = $this->priceUtil->getUnitPrice($orderItem, (int)$orderItem->getStoreId());

        self::assertEqualsWithDelta(
            $this->getExpectedBaseUnitPriceExclTax($orderItem),
            $unitPrice,
            0.01
        );

        $itemContributionInclTax = ($orderItem->getBaseRowTotal() - $orderItem->getBaseDiscountAmount())
            * $taxMultiplier;

        self::assertEqualsWithDelta($itemContributionInclTax, $unitPrice * $qty * $taxMultiplier, 0.01);
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
     * Get the expected base unit price excluding tax.
     *
     * Formula: (base_row_total - base_discount_amount + base_discount_tax_compensation_amount) / qty
     *
     * @param OrderItemInterface $item
     * @return float
     */
    private function getExpectedBaseUnitPriceExclTax(OrderItemInterface $item): float
    {
        $quantity = (float)$item->getQtyOrdered();

        return ($item->getBaseRowTotal() - $item->getBaseDiscountAmount()
                + $item->getBaseDiscountTaxCompensationAmount()) / $quantity;
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
