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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\ShoppingCartBuilder;

use Magento\Sales\Model\Order\Item;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart\Item as TransactionItem;
use MultiSafepay\ValueObject\Money;

class OrderItemBuilder
{
    /**
     * @param Item $item
     * @param string $currency
     * @return TransactionItem
     */
    public function build(Item $item, string $currency): TransactionItem
    {
        return (new TransactionItem())
            ->addName($item->getName())
            ->addUnitPrice(new Money(round($this->getPrice($item) * 100, 10), $currency))
            ->addQuantity((int)$item->getQtyOrdered())
            ->addDescription($this->getDescription($item))
            ->addMerchantItemId($item->getSku())
            ->addTaxRate((float)$item->getTaxPercent());
    }

    /**
     * @param Item $item
     * @return string
     */
    public function getDescription(Item $item): string
    {
        $description = $item->getDescription();

        if (empty($description)) {
            return '';
        }
        return $description;
    }

    /**
     * @param Item $item
     * @return float
     */
    public function getPrice(Item $item): float
    {
        return ($item->getBasePrice() - ($item->getBaseDiscountAmount() / $item->getQtyOrdered()));
    }
}
