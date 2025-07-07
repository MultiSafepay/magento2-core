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

namespace MultiSafepay\ConnectCore\Util;

use Magento\Bundle\Model\Product\Price;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Item;
use Magento\Sales\Model\Order\Item as OrderItem;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\ConnectCore\Config\Config;

class ShoppingCartRefundUtil
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Build the items that need to be refunded
     *
     * @param Creditmemo $creditMemo
     * @param TransactionResponse $transaction
     * @return array
     */
    public function buildItems(Creditmemo $creditMemo, TransactionResponse $transaction): array
    {
        $itemsToRefund = [];
        $transactionVar1 = $transaction->getVar1();

        /** @var Item $item */
        foreach ($creditMemo->getItems() as $item) {
            $refundItem = $this->processRefundItem($item, $transactionVar1);
            if ($refundItem !== null) {
                $itemsToRefund[] = $refundItem;
            }
        }

        return $itemsToRefund;
    }

    /**
     * Process a credit memo item and determine if it should be refunded
     *
     * @param Item $item
     * @param string $var1
     * @return array|null
     */
    private function processRefundItem(Item $item, string $var1): ?array
    {
        $orderItem = $item->getOrderItem();
        $product = $orderItem->getProduct();

        if (!$product || $item->getQty() <= 0) {
            return null;
        }

        // Skip dynamic price bundle products
        if ($this->isDynamicPriceBundle($orderItem, $product)) {
            return null;
        }

        $parentItem = $orderItem->getParentItem();

        // Handle standalone items
        if ($parentItem === null) {
            return $this->createRefundItemData($item, $var1);
        }

        // Handle child items
        return $this->processChildItem($item, $parentItem, $var1);
    }

    /**
     * Check if item is a dynamic price bundle product
     *
     * @param OrderItem $orderItem
     * @param Product $product
     * @return bool
     */
    private function isDynamicPriceBundle(OrderItem $orderItem, Product $product): bool
    {
        return $orderItem->getProductType() === Type::TYPE_BUNDLE
            && (int)$product->getPriceType() === Price::PRICE_TYPE_DYNAMIC;
    }

    /**
     * Process a child item to determine if it should be refunded
     *
     * @param Item $item
     * @param OrderItem $parentItem
     * @param string $var1
     * @return array|null
     */
    private function processChildItem(Item $item, OrderItem $parentItem, string $var1): ?array
    {
        // We only want child items of bundle products
        if ($parentItem->getProductType() !== Type::TYPE_BUNDLE) {
            return null;
        }

        // Skip children of fixed price bundle products
        if ($this->isFixedPriceBundle($parentItem)) {
            return null;
        }

        return $this->createRefundItemData($item, $var1);
    }

    /**
     * Check if the parent item is a fixed price bundle product
     *
     * @param OrderItem $parentItem
     * @return bool
     */
    private function isFixedPriceBundle(OrderItem $parentItem): bool
    {
        return ($parentItem->getProduct() !== null)
            && (int)$parentItem->getProduct()->getPriceType() === Price::PRICE_TYPE_FIXED;
    }

    /**
     * Create refund item data array
     *
     * @param Item $item
     * @param string $var1
     * @return array
     */
    private function createRefundItemData(Item $item, string $var1): array
    {
        return [
            'merchant_item_id' => $this->getMerchantItemId($item, $var1),
            'quantity' => (int) $item->getQty(),
        ];
    }

    /**
     * Retrieve the shipping amount from the credit memo
     *
     * @param Creditmemo $creditMemo
     * @return float|null
     */
    public function getShippingAmount(Creditmemo $creditMemo): ?float
    {
        if ($this->config->useBaseCurrency($creditMemo->getStoreId())) {
            return $creditMemo->getBaseShippingAmount();
        }

        return $creditMemo->getShippingAmount();
    }

    /**
     * Retrieve the correct adjustment from the credit memo
     *
     * @param Creditmemo $creditMemo
     * @return float|null
     */
    public function getAdjustment(Creditmemo $creditMemo): ?float
    {
        if ($this->config->useBaseCurrency($creditMemo->getStoreId())) {
            return $creditMemo->getBaseAdjustment();
        }

        return $creditMemo->getAdjustment();
    }

    /**
     * Get Fooman Surcharge data from extension attributes
     *
     * @param mixed $extensionAttributes
     * @return array|null
     */
    public function getFoomanSurcharge($extensionAttributes): ?array
    {
        if (!method_exists($extensionAttributes, 'getFoomanTotalGroup')) {
            return null;
        }

        if ($extensionAttributes->getFoomanTotalGroup() === null) {
            return null;
        }

        $foomanSurcharge = [];

        foreach ($extensionAttributes->getFoomanTotalGroup()->getItems() as $foomanTotal) {
            if (empty($foomanSurcharge)) {
                $foomanSurcharge = ['amount' => 0.0, 'base_amount' => 0.0,];
            }

            $foomanSurcharge['amount'] += (float)($foomanTotal->getAmount());
            $foomanSurcharge['base_amount'] += (float)($foomanTotal->getBaseAmount());
            $foomanSurcharge['tax_rate'] = (float)$foomanTotal->getTaxPercent();
        }

        return !empty($foomanSurcharge) ? $foomanSurcharge : null;
    }

    /**
     * Retrieve the merchant item id. It should be SKU for versions older than 3.2.0 or SKU_QuoteItemID
     *
     * @param Item $item
     * @param string $var1
     * @return string
     */
    private function getMerchantItemId(Item $item, string $var1): string
    {
        if (empty($var1) || version_compare($var1, '3.2.0', '<')) {
            return $item->getSku();
        }

        return $item->getSku() . '_' . $item->getOrderItem()->getQuoteItemId();
    }
}
