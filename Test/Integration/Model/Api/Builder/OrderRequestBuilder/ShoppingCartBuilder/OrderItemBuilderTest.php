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

namespace MultiSafepay\ConnectCore\Test\Integration\Model\Api\Builder\OrderRequestBuilder\ShoppingCartBuilder;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\ShoppingCartBuilder\OrderItemBuilder;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\CurrencyUtil;
use MultiSafepay\ConnectCore\Util\PriceUtil;
use ReflectionException;

class OrderItemBuilderTest extends AbstractTestCase
{
    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     *
     * @throws LocalizedException
     * @throws ReflectionException
     */
    public function testOrderItemProperties(): void
    {
        $order = $this->getOrder();
        $currency = $this->getCurrencyUtil()->getCurrencyCode($order);

        $items = $this->getOrderItemBuilder()->build($order, $currency);
        $item = $this->convertObjectToArray($items[0]);

        $expectedItems = $order->getItems();
        $expectedItem = reset($expectedItems);

        self::assertSame($expectedItem->getName(), $item['name']);
        self::assertSame((float)$expectedItem->getQtyOrdered(), $item['quantity']);
        self::assertSame($expectedItem->getSku(), $item['merchantItemId']);
        self::assertSame($expectedItem->getDescription() ?? '', $item['description']);
        self::assertSame((float)$expectedItem->getTaxPercent(), $item['taxRate']);

        $unitPrice = $this->convertObjectToArray($item['unitPrice']);

        self::assertSame(
            $this->getPriceUtil()->getUnitPrice($expectedItem, $order->getStoreId()) * 100,
            $unitPrice['amount']
        );
        self::assertSame($currency, $unitPrice['currency']);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order_with_two_order_items_with_simple_product.php
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     *
     * @throws LocalizedException
     */
    public function testOrderItemsWithMultipleProducts(): void
    {
        $order = $this->getObjectManager()->create(Order::class);
        $order->loadByIncrementId('100000001');

        $currency = $this->getCurrencyUtil()->getCurrencyCode($order);

        $items = $this->getOrderItemBuilder()->build($order, $currency);

        self::assertCount(2, $items);
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

    /**
     * @return PriceUtil
     */
    private function getPriceUtil(): PriceUtil
    {
        return $this->getObjectManager()->create(PriceUtil::class);
    }
}
