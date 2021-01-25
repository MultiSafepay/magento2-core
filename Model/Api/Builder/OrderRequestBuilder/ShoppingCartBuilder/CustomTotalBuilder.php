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

use Magento\Framework\Phrase;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart\Item;
use MultiSafepay\ValueObject\Money;

class CustomTotalBuilder
{
    public const DEFAULT_TOTALS = [
        'subtotal',
        'shipping',
        'tax',
        'grand_total',
        'mp_reward_spent',
        'mp_reward_earn'
    ];

    /**
     * @param $total
     * @param string $currency
     * @return Item
     */
    public function build($total, string $currency): Item
    {
        return (new Item())
            ->addName($this->getTitle($total))
            ->addUnitPrice(new Money($total->getValue() * 100, $currency))
            ->addQuantity(1)
            ->addDescription($this->getTitle($total))
            ->addMerchantItemId($total->getCode())
            ->addTaxRate($this->getTaxRate($total));
    }

    /**
     * @param $total
     * @return float
     */
    public function getTaxRate($total): float
    {
        if (empty($total->getTaxAmount())) {
            return 0;
        }
        return (round($total->getTaxAmount() / $total->getValue()));
    }

    /**
     * @param $total
     * @return string
     */
    public function getTitle($total): string
    {
        $title = $total->getTitle();

        if ($title instanceof Phrase) {
            return (string) $title->getText();
        }
        return (string) $title;
    }
}
