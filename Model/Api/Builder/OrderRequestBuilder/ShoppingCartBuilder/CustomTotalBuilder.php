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
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ValueObject\Money;

class CustomTotalBuilder
{
    public const EXCLUDED_TOTALS = [
        'subtotal',
        'shipping',
        'tax',
        'grand_total',
        'fooman_surcharge_tax_after',
        'mp_reward_spent',
        'mp_reward_earn',
    ];

    /**
     * @var Config
     */
    private $config;

    /**
     * CustomTotalBuilder constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param $total
     * @param string $currency
     * @param $storeId
     * @return Item
     */
    public function build($total, string $currency, $storeId): Item
    {
        $title = $this->getTitle($total);
        $unitPrice = $total->getAmount() ? $this->getAmount($total, $storeId) : $total->getValue();

        return (new Item())
            ->addName($title)
            ->addUnitPrice(new Money(round($unitPrice * 100, 10), $currency))
            ->addQuantity(1)
            ->addDescription($title)
            ->addMerchantItemId($total->getCode())
            ->addTaxRate($this->getTaxRate($total, $storeId));
    }

    /**
     * @param $total
     * @return float
     */
    public function getTaxRate($total, $storeId): float
    {
        if ($total->getValue()) {
            return round($total->getTaxAmount() / $total->getValue() * 100);
        }

        if ($this->config->useBaseCurrency($storeId)) {
            return round($total->getBaseTaxAmount() / $total->getBaseAmount() * 100);
        }

        return round($total->getTaxAmount() / $total->getAmount() * 100);
    }

    /**
     * @param $total
     * @param $storeId
     * @return float
     */
    public function getAmount($total, $storeId): float
    {
        if ($this->config->useBaseCurrency($storeId)) {
            return (float)$total->getBaseAmount();
        }

        return (float)$total->getAmount();
    }

    /**
     * @param $total
     * @return string
     */
    public function getTitle($total): string
    {
        $title = $total->getTitle() ?: $total->getLabel();

        if ($title instanceof Phrase) {
            return (string) $title->render();
        }

        return (string) $title;
    }
}
