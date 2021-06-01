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

namespace MultiSafepay\ConnectCore\Test\Integration\Model\Api\Builder\OrderRequestBuilder;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\ShoppingCartBuilder;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\AfterpayConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\IdealConfigProvider;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class ShoppingCartBuilderTest extends AbstractTestCase
{
    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/advanced/disable_shopping_cart 0
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     *
     * @throws LocalizedException
     */
    public function testIsShoppingCartNeededReturnsTrueIfEnabledWithShoppingCartMethod(): void
    {
        $orderRequest = $this->prepareOrderRequest();
        $orderRequest->addGatewayCode(AfterpayConfigProvider::CODE);

        self::assertNotEmpty($orderRequest->getShoppingCart());
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/advanced/disable_shopping_cart 1
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     *
     * @throws LocalizedException
     */
    public function testIsShoppingCartNeededReturnsTrueIfDisabledWithShoppingCartMethod(): void
    {
        $orderRequest = $this->prepareOrderRequest();
        $orderRequest->addGatewayCode(AfterpayConfigProvider::CODE);

        self::assertArrayNotHasKey('shopping_cart', $orderRequest->getData());
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/advanced/disable_shopping_cart 1
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     *
     * @throws LocalizedException
     */
    public function testIsShoppingCartNeededReturnsFalseIfEnabledWithoutShoppingCartMethod(): void
    {
        $orderRequest = $this->prepareOrderRequest();
        $orderRequest->addGatewayCode(IdealConfigProvider::CODE);

        self::assertArrayNotHasKey('shopping_cart', $orderRequest->getData());
    }

    /**
     * @magentoDataFixture   Magento/Customer/_files/customer.php
     * @magentoDataFixture   Magento/Catalog/_files/product_simple.php
     * @magentoConfigFixture default_store multisafepay/advanced/disable_shopping_cart 0
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     *
     * @throws LocalizedException
     * @throws Exception
     */
    public function testShoppingCartBuilderPutsAllItemsInOrderRequest(): void
    {
        $this->includeFixtureFile('product_simple_without_custom_options');
        $this->includeFixtureFile('order_with_two_simple_products');
        $orderRequest = $this->prepareOrderRequest();

        self::assertArrayHasKey('shopping_cart', $orderRequest->getData());

        $merchantItemIds = [];

        foreach ($orderRequest->getData()['shopping_cart']['items'] as $item) {
            $merchantItemIds[] = $item['merchant_item_id'];
        }

        self::assertSame(['simple', 'simple-2'], $merchantItemIds);
    }

    /**
     * @return OrderRequest
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function prepareOrderRequest(): OrderRequest
    {
        $order = $this->getObjectManager()->create(Order::class);
        $order->loadByIncrementId('100000001');
        $payment = $order->getPayment();
        $orderRequest = $this->getOrderRequestBuilder()->build($order);
        $this->getShoppingCartBuilder()->build($order, $payment, $orderRequest);

        return $orderRequest;
    }

    /**
     * @return ShoppingCartBuilder
     */
    private function getShoppingCartBuilder(): ShoppingCartBuilder
    {
        return $this->getObjectManager()->create(ShoppingCartBuilder::class);
    }

    /**
     * @return OrderRequestBuilder
     */
    private function getOrderRequestBuilder(): OrderRequestBuilder
    {
        return $this->getObjectManager()->create(OrderRequestBuilder::class);
    }
}
