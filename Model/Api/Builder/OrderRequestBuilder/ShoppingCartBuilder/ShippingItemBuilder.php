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

use Magento\Sales\Api\Data\OrderInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart\Item;
use MultiSafepay\ValueObject\Money;

class ShippingItemBuilder
{

    /**
     * @param OrderInterface $order
     * @param string $currency
     * @return Item
     */
    public function build(OrderInterface $order, string $currency): Item
    {
        return (new Item())
            ->addName($order->getShippingDescription())
            ->addUnitPrice(new Money((float) $order->getBaseShippingAmount() * 100, $currency))
            ->addQuantity(1)
            ->addDescription('Shipping')
            ->addMerchantItemId('msp-shipping')
            ->addTaxRate($this->getShippingTaxRate($order));
    }

    /**
     * @param OrderInterface $order
     * @return float
     */
    public function getShippingTaxRate(OrderInterface $order): float
    {
        $shippingTaxAmount = $order->getBaseShippingTaxAmount();
        $originalShippingAmount = $order->getBaseShippingInclTax() - $shippingTaxAmount;

        return (float) $shippingTaxAmount / $originalShippingAmount * 100;
    }
}
