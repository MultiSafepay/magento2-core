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

use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Item;
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

        /** @var Item $item */
        foreach ($creditMemo->getItems() as $item) {
            $orderItem = $item->getOrderItem();

            if ($orderItem === null) {
                continue;
            }

            // Don't add the item if it's a bundle product
            if ($orderItem->getProductType() === 'bundle') {
                continue;
            }

            // Don't add the item if it has a parent item and the parent item is not a bundle
            if ($orderItem->getParentItem() !== null && !$this->isParentItemBundle($orderItem)) {
                continue;
            }

            if ($item->getQty() > 0) {
                $itemsToRefund[] = [
                    'merchant_item_id' => $this->getMerchantItemId($item, $transaction->getVar1()),
                    'quantity' => (int) $item->getQty(),
                ];
            }
        }

        return $itemsToRefund;
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
     * Check if the parent item is bundle
     *
     * @param OrderItemInterface $orderItem
     * @return bool
     */
    private function isParentItemBundle(OrderItemInterface $orderItem): bool
    {
        $parentItem = $orderItem->getParentItem();

        // Parent item is not bundle, because it doesn't exist
        if ($parentItem === null) {
            return false;
        }

        if ($parentItem->getProductType() === 'bundle') {
            return true;
        }

        return false;
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
