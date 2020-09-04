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

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart\Item;
use MultiSafepay\ValueObject\Money;

class FoomanSurchargeTotalBuilder
{

    /**
     * @param \Fooman\Totals\Api\Data\TotalInterface $total
     * @param string $currency
     * @return mixed
     */
    public function build(\Fooman\Totals\Api\Data\TotalInterface $total, string $currency)
    {
        return (new Item())
            ->addName($total->getLabel())
            ->addUnitPrice(new Money((float) $total->getAmount() * 100, $currency))
            ->addQuantity(1)
            ->addDescription($total->getLabel())
            ->addMerchantItemId('fooman-surcharge')
            ->addTaxRate($this->getTaxRate($total));
    }

    /**
     * @param \Fooman\Totals\Api\Data\TotalInterface $total
     * @return float
     */
    public function getTaxRate(\Fooman\Totals\Api\Data\TotalInterface $total): float
    {
        return (round($total->getTaxAmount() / $total->getAmount() * 100));
    }
}
