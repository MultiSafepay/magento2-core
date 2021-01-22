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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\ShoppingCartBuilder;

use Magento\Sales\Model\Order\Item;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart\Item as TransactionItem;
use MultiSafepay\ConnectCore\Util\PriceUtil;
use MultiSafepay\ValueObject\Money;

class OrderItemBuilder
{
    /**
     * @var PriceUtil
     */
    private $priceUtil;

    /**
     * OrderItemBuilder constructor.
     *
     * @param PriceUtil $priceUtil
     */
    public function __construct(
        PriceUtil $priceUtil
    ) {
        $this->priceUtil = $priceUtil;
    }

    /**
     * @param Item $item
     * @param string $currency
     * @param string $storeId
     * @return TransactionItem
     */
    public function build(Item $item, string $currency, $storeId): TransactionItem
    {
        $unitPrice = $this->priceUtil->getUnitPrice($item, $storeId);

        return (new TransactionItem())
            ->addName($item->getName())
            ->addUnitPrice(new Money(round($unitPrice * 100, 10), $currency))
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
}
