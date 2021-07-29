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

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart\Item;
use MultiSafepay\ConnectCore\Util\PriceUtil;
use MultiSafepay\ConnectCore\Util\TaxUtil;
use MultiSafepay\ValueObject\Money;

class ShippingItemBuilder implements ShoppingCartBuilderInterface
{
    public const SHIPPING_ITEM_MERCHANT_ITEM_ID = 'msp-shipping';

    /**
     * @var PriceUtil
     */
    private $priceUtil;

    /**
     * @var TaxUtil
     */
    private $taxUtil;

    /**
     * ShippingItemBuilder constructor.
     *
     * @param PriceUtil $priceUtil
     * @param TaxUtil $taxUtil
     */
    public function __construct(
        PriceUtil $priceUtil,
        TaxUtil $taxUtil
    ) {
        $this->priceUtil = $priceUtil;
        $this->taxUtil = $taxUtil;
    }

    /**
     * @param OrderInterface $order
     * @param string $currency
     * @return Item[]
     * @throws NoSuchEntityException
     */
    public function build(OrderInterface $order, string $currency): array
    {
        $items = [];

        if ($order->getShippingAmount() > 0) {
            $shippingPrice = $this->priceUtil->getShippingUnitPrice($order);

            $items[] = (new Item())
                ->addName($order->getShippingDescription() ?? 'shipment')
                ->addUnitPrice(new Money($shippingPrice * 100, $currency))
                ->addQuantity(1)
                ->addDescription('Shipping')
                ->addMerchantItemId(self::SHIPPING_ITEM_MERCHANT_ITEM_ID)
                ->addTaxRate($this->taxUtil->getShippingTaxRate($order));
        }

        return $items;
    }
}
