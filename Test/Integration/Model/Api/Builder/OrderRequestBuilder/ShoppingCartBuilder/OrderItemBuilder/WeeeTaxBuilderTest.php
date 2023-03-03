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

//phpcs:ignore
namespace MultiSafepay\ConnectCore\Test\Integration\Model\Api\Builder\OrderRequestBuilder\ShoppingCartBuilder\OrderItemBuilder;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart\Item;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\ShoppingCartBuilder\OrderItemBuilder;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\CurrencyUtil;
use MultiSafepay\ValueObject\Money;

class WeeeTaxBuilderTest extends AbstractTestCase
{

    /**
     * Test FPT for one order item excluding tax
     *
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store tax/weee/apply_vat 0
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/use_base_currency 1
     *
     * @throws LocalizedException
     * @throws Exception
     */
    public function testOrderItemsWithFPTExclTaxAndBaseCurrency(): void
    {
        $order = $this->getObjectManager()->create(Order::class);
        $order->loadByIncrementId('100000001');

        foreach ($order->getItems() as $orderItem) {
            $orderItem->setStoreId(1);
            $orderItem->setWeeeTaxApplied(
                '[{"title":"FPT","base_amount":"0.6000","amount":0.2,"row_amount":0.2,
                "base_row_amount":0.6,"base_amount_incl_tax":"0.6000","amount_incl_tax":0.2,"row_amount_incl_tax":0.2,
                "base_row_amount_incl_tax":0.6}]'
            );
        }

        /** @var Item[] $items */
        $items = $this->getOrderItemBuilder()->build($order, $this->getCurrencyUtil()->getCurrencyCode($order));

        self::assertCount(2, $items);
        self::assertEquals('FPT', $items[1]->getDescription());
        self::assertEquals('FPT', $items[1]->getMerchantItemId());
        self::assertEquals(new Money(60, 'USD'), $items[1]->getUnitPrice());
        self::assertEquals(1, $items[1]->getQuantity());
    }

    /**
     * Test FPT for multiple order items excluding tax
     *
     * @magentoDataFixture   Magento/Sales/_files/order_with_two_order_items_with_simple_product.php
     * @magentoConfigFixture default_store tax/weee/apply_vat 0
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     *
     * @throws LocalizedException
     * @throws Exception
     */
    public function testOrderItemsWithFPTExclTaxAndMultipleItems(): void
    {
        $order = $this->getObjectManager()->create(Order::class);
        $order->loadByIncrementId('100000001');

        foreach ($order->getItems() as $orderItem) {
            $orderItem->setWeeeTaxApplied(
                '[{"title":"FPT","base_amount":"0.6000","amount":0.6,"row_amount":3,
                "base_row_amount":3,"base_amount_incl_tax":"0.6000","amount_incl_tax":0.6,"row_amount_incl_tax":3,
                "base_row_amount_incl_tax":3}]'
            );
        }

        /** @var Item[] $items */
        $items = $this->getOrderItemBuilder()->build($order, $this->getCurrencyUtil()->getCurrencyCode($order));

        self::assertCount(3, $items);
        self::assertEquals('FPT', $items[2]->getDescription());
        self::assertEquals('FPT', $items[2]->getMerchantItemId());
        self::assertEquals(new Money(600, 'USD'), $items[2]->getUnitPrice());
        self::assertEquals(1, $items[2]->getQuantity());
    }

    /**
     * Test FPT for one order item including tax
     *
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store tax/weee/apply_vat 1
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     *
     * @throws LocalizedException
     * @throws Exception
     */
    public function testOrderItemsWithFPTInclTax(): void
    {
        $order = $this->getObjectManager()->create(Order::class);
        $order->loadByIncrementId('100000001');

        foreach ($order->getItems() as $orderItem) {
            $orderItem->setWeeeTaxApplied(
                '[{"title":"FPT","base_amount":"0.6000","amount":0.6,"row_amount":0.6,
                "base_row_amount":0.6,"base_amount_incl_tax":"0.73","amount_incl_tax":0.73,"row_amount_incl_tax":0.73,
                "base_row_amount_incl_tax":0.73}]'
            );
            $orderItem->setTaxPercent(9);
        }

        /** @var Item[] $items */
        $items = $this->getOrderItemBuilder()->build($order, $this->getCurrencyUtil()->getCurrencyCode($order));

        self::assertCount(2, $items);
        self::assertEquals('FPT', $items[1]->getDescription());
        self::assertEquals('FPT9', $items[1]->getMerchantItemId());
        self::assertEquals(new Money(60, 'USD'), $items[1]->getUnitPrice());
        self::assertEquals(1, $items[1]->getQuantity());
    }

    /**
     * Test FPT for multiple order items including tax
     *
     * @magentoDataFixture   Magento/Sales/_files/order_with_two_order_items_with_simple_product.php
     * @magentoConfigFixture default_store tax/weee/apply_vat 1
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     *
     * @throws LocalizedException
     * @throws Exception
     */
    public function testOrderItemsWithFPTInclTaxAndMultipleItems(): void
    {
        $order = $this->getObjectManager()->create(Order::class);
        $order->loadByIncrementId('100000001');

        $taxes = [9, 21];
        $index = 0;

        foreach ($order->getItems() as $orderItem) {
            $orderItem->setWeeeTaxApplied(
                '[{"title":"FPT","base_amount":"0.6000","amount":0.6,"row_amount":0.6,
                "base_row_amount":0.6,"base_amount_incl_tax":"0.73","amount_incl_tax":0.73,"row_amount_incl_tax":0.73,
                "base_row_amount_incl_tax":0.73}]'
            );
            $orderItem->setTaxPercent($taxes[$index]);
            $index++;
        }

        /** @var Item[] $items */
        $items = $this->getOrderItemBuilder()->build($order, $this->getCurrencyUtil()->getCurrencyCode($order));

        self::assertCount(4, $items);

        $index = 0;

        foreach ($items as $item) {
            if ($item->getDescription() === 'FPT') {
                self::assertEquals('FPT', $item->getDescription());
                self::assertEquals('FPT' . $taxes[$index - 2], $item->getMerchantItemId());
                self::assertEquals(new Money(60, 'USD'), $item->getUnitPrice());
                self::assertEquals(1, $item->getQuantity());
            }
            $index++;
        }
    }

    /**
     * @return OrderItemBuilder
     */
    private function getOrderItemBuilder(): OrderItemBuilder
    {
        return $this->getObjectManager()->create(OrderItemBuilder::class);
    }

    /**
     * @return CurrencyUtil
     */
    private function getCurrencyUtil(): CurrencyUtil
    {
        return $this->getObjectManager()->create(CurrencyUtil::class);
    }
}
