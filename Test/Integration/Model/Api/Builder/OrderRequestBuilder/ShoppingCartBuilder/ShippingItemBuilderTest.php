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

namespace MultiSafepay\ConnectCore\Test\Integration\Model\Api\Builder\OrderRequestBuilder\ShoppingCartBuilder;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart\Item;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\ShoppingCartBuilder\ShippingItemBuilder;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\CurrencyUtil;

class ShippingItemBuilderTest extends AbstractTestCase
{
    /**
     * @var ShippingItemBuilder
     */
    private $shippingItemBuilder;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CurrencyUtil
     */
    private $currencyUtil;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->shippingItemBuilder = $this->getObjectManager()->create(ShippingItemBuilder::class);
        $this->config = $this->getObjectManager()->create(Config::class);
        $this->currencyUtil = $this->getObjectManager()->create(CurrencyUtil::class);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDataFixture   Magento/Sales/_files/order_with_shipping_and_invoice.php
     * @magentoDataFixture   Magento/Sales/_files/quote_with_multiple_products.php
     * @magentoConfigFixture default_store multisafepay/general/use_base_currency 1
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function testBuildWithUseBaseCurrencySettingEnabled(): void
    {
        $order = $this->getOrder();
        $this->checkBuiltShippingItem($order, 20);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDataFixture   Magento/Sales/_files/order_with_shipping_and_invoice.php
     * @magentoDataFixture   Magento/Sales/_files/quote_with_multiple_products.php
     * @magentoConfigFixture default_store multisafepay/general/use_base_currency 0
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function testBuildWithUseBaseCurrencySettingDisabled(): void
    {
        $order = $this->getOrder();
        $this->checkBuiltShippingItem($order, 25);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDataFixture   Magento/Sales/_files/order_with_shipping_and_invoice.php
     * @magentoDataFixture   Magento/Sales/_files/quote_with_multiple_products.php
     * @magentoConfigFixture default_store multisafepay/general/use_base_currency 0
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function testBuildWithShippingAmountEqualsZero(): void
    {
        $order = $this->getOrder();
        $shippingItem = $this->getBuiltShippingItem($order, 0);

        self::assertNull($shippingItem);
    }

    /**
     * @param OrderInterface $order
     * @param float|null $customShippingAmount
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function checkBuiltShippingItem(OrderInterface $order, ?float $customShippingAmount = null): void
    {
        $shippingItem = $this->getBuiltShippingItem($order, $customShippingAmount);

        $shippingAmount = $this->config->useBaseCurrency($order->getStoreId()) ? $order->getBaseShippingAmount() :
            $order->getShippingAmount();

        self::assertEquals($shippingAmount * 100, $shippingItem->getUnitPrice()->getAmount());
        self::assertEquals($this->currencyUtil->getCurrencyCode($order), $shippingItem->getUnitPrice()->getCurrency());
        self::assertEquals($order->getShippingDescription(), $shippingItem->getName());
        self::assertEquals(1, $shippingItem->getQuantity());
        self::assertEquals(
            ShippingItemBuilder::SHIPPING_ITEM_MERCHANT_ITEM_ID,
            $shippingItem->getMerchantItemId()
        );

        self::assertEquals(0.0, $shippingItem->getTaxRate());
    }

    /**
     * @param OrderInterface $order
     * @param float|null $customShippingAmount
     * @return Item|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    private function getBuiltShippingItem(OrderInterface $order, ?float $customShippingAmount = null): ?Item
    {
        $quote = $this->getQuote('tableRate');
        $order->setShippingDescription('test_shipping')
            ->setQuoteId($quote->getId())
            ->setShippingAmount($customShippingAmount)
            ->setBaseShippingInclTax($customShippingAmount)
            ->setShippingInclTax($customShippingAmount);

        $builtItems = $this->shippingItemBuilder->build($order, $this->currencyUtil->getCurrencyCode($order));

        return $builtItems && isset($builtItems[0]) ? $builtItems[0] : null;
    }
}
