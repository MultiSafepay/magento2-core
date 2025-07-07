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

namespace MultiSafepay\Test\Integration\Util;

use Magento\Catalog\Model\Product;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Item;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\ShoppingCartRefundUtil;

class ShoppingCartRefundUtilTest extends AbstractTestCase
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Creditmemo
     */
    private $creditMemo;

    /**
     * @var TransactionResponse
     */
    private $transaction;

    /**
     * @var Item
     */
    private $item;

    /**
     * @var OrderItemInterface
     */
    private $orderItem;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->config = $this->getObjectManager()->create(Config::class);
        $this->creditMemo = $this->getObjectManager()->create(Creditmemo::class);
        $this->transaction = $this->createMock(TransactionResponse::class);
        $this->item = $this->getObjectManager()->create(Item::class);
        $this->orderItem = $this->getObjectManager()->create(OrderItemInterface::class);
    }

    /**
     * Test that bundled items are not included in the refund
     *
     * @return void
     */
    public function testBuildItemsToRefundExcludingBundles()
    {
        $this->orderItem->setProductType('bundle');
        $this->item->setOrderItem($this->orderItem);
        $this->creditMemo->setItems([$this->item]);

        $shoppingCartRefundUtil = $this->getObjectManager()->create(ShoppingCartRefundUtil::class, [$this->config]);

        $this->assertEquals([], $shoppingCartRefundUtil->buildItems($this->creditMemo, $this->transaction));
    }

    /**
     * Test that valid items are added to the refund correctly
     *
     * @return void
     */
    public function testBuildItemsToRefundWithValidItems()
    {
        // Create a product for the order item since it's checked in processRefundItem
        $product = $this->createMock(Product::class);

        $this->orderItem->setProductType('simple');
        $this->orderItem->setProduct($product); // Add product to order item
        $this->item->setOrderItem($this->orderItem);
        $this->item->setQty(2);
        $this->item->setSku('sku123');
        $this->transaction->method('getVar1')->willReturn('3.10.0');
        $this->orderItem->setQuoteItemId(10);
        $this->creditMemo->setItems([$this->item]);

        $shoppingCartRefundUtil = $this->getObjectManager()->create(ShoppingCartRefundUtil::class, [$this->config]);
        $expected = [['merchant_item_id' => 'sku123_10', 'quantity' => 2]];

        $this->assertEquals($expected, $shoppingCartRefundUtil->buildItems($this->creditMemo, $this->transaction));
    }

    /**
     * Test that the shipping item is correctly retrieved using the base currency
     *
     * @return void
     */
    public function testRetrievesShippingAmountCorrectly()
    {
        $this->creditMemo->setBaseShippingAmount(4.62);

        $shoppingCartRefundUtil = $this->getObjectManager()->create(ShoppingCartRefundUtil::class, [$this->config]);

        $this->assertEquals(4.62, $shoppingCartRefundUtil->getShippingAmount($this->creditMemo));
    }

    /**
     * Test that the adjustment item is correctly created using the base currency
     *
     * @return void
     */
    public function testRetrievesAdjustmentCorrectly()
    {
        $this->creditMemo->setBaseAdjustment(3.12);

        $shoppingCartRefundUtil = $this->getObjectManager()->create(ShoppingCartRefundUtil::class, [$this->config]);

        $this->assertEquals(3.12, $shoppingCartRefundUtil->getAdjustment($this->creditMemo));
    }

    /**
     * Test that the merchant item ID is retrieved correctly for older versions
     *
     * @return void
     */
    public function testRetrievesMerchantItemIdForOlderVersion()
    {
        // Create a product mock since it's now checked in processRefundItem
        $product = $this->createMock(Product::class);

        $this->orderItem->setProductType('simple');
        $this->orderItem->setProduct($product); // Add product to order item
        $this->item->setOrderItem($this->orderItem);
        $this->item->setSku('sku123');
        $this->item->setQty(1);
        $this->creditMemo->setItems([$this->item]);
        $this->transaction->method('getVar1')->willReturn('3.1.0');

        $expected = [['merchant_item_id' => 'sku123', 'quantity' => 1]];
        $shoppingCartRefundUtil = $this->getObjectManager()->create(ShoppingCartRefundUtil::class, [$this->config]);

        $this->assertEquals($expected, $shoppingCartRefundUtil->buildItems($this->creditMemo, $this->transaction));
    }

    /**
     * Test getFoomanSurcharge method when method doesn't exist
     *
     * @return void
     */
    public function testGetFoomanSurchargeWhenMethodDoesntExist(): void
    {
        $extensionAttributes = new \stdClass();

        $shoppingCartRefundUtil = $this->getObjectManager()->create(ShoppingCartRefundUtil::class, [$this->config]);

        self::assertNull($shoppingCartRefundUtil->getFoomanSurcharge($extensionAttributes));
    }

    /**
     * Test getFoomanSurcharge method when FoomanTotalGroup is null
     *
     * @return void
     */
    public function testGetFoomanSurchargeWhenTotalGroupIsNull(): void
    {
        $extensionAttributes = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getFoomanTotalGroup'])
            ->getMock();

        $extensionAttributes->method('getFoomanTotalGroup')->willReturn(null);

        $shoppingCartRefundUtil = $this->getObjectManager()->create(ShoppingCartRefundUtil::class, [$this->config]);

        self::assertNull($shoppingCartRefundUtil->getFoomanSurcharge($extensionAttributes));
    }

    /**
     * Test getFoomanSurcharge method with valid data
     *
     * @return void
     */
    public function testGetFoomanSurchargeWithValidData(): void
    {
        $foomanTotal = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getAmount', 'getBaseAmount', 'getTaxPercent'])
            ->getMock();

        $foomanTotal->method('getAmount')->willReturn(10.0);
        $foomanTotal->method('getBaseAmount')->willReturn(12.0);
        $foomanTotal->method('getTaxPercent')->willReturn(21.0);

        $foomanTotalGroup = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getItems'])
            ->getMock();

        $foomanTotalGroup->method('getItems')->willReturn([$foomanTotal]);

        $extensionAttributes = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getFoomanTotalGroup'])
            ->getMock();

        $extensionAttributes->method('getFoomanTotalGroup')->willReturn($foomanTotalGroup);

        $shoppingCartRefundUtil = $this->getObjectManager()->create(ShoppingCartRefundUtil::class, [$this->config]);
        $result = $shoppingCartRefundUtil->getFoomanSurcharge($extensionAttributes);

        self::assertIsArray($result);
        self::assertEquals(10.0, $result['amount']);
        self::assertEquals(12.0, $result['base_amount']);
        self::assertEquals(21.0, $result['tax_rate']);
    }
}
