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

namespace MultiSafepay\ConnectCore\Util;

use Magento\Directory\Model\PriceCurrency;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Tax\Model\Config as MagentoConfig;
use MultiSafepay\ConnectCore\Config\Config;

class PriceUtil
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * PriceUtil constructor.
     *
     * @param Config $config
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Config $config,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param OrderInterface $order
     * @return float
     */
    public function getGrandTotal(OrderInterface $order): float
    {
        if ($this->config->useBaseCurrency($order->getStoreId())) {
            return (float)$order->getBaseGrandTotal();
        }

        return (float)$order->getGrandTotal();
    }

    /**
     * @param OrderItemInterface $item
     * @param $storeId
     * @return float
     */
    public function getUnitPrice(OrderItemInterface $item, $storeId): float
    {
        $orderedQuantity = (float)$item->getQtyOrdered();

        $isPriceIncludedTax = $this->scopeConfig->getValue(
            MagentoConfig::CONFIG_XML_PATH_PRICE_INCLUDES_TAX,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $isPriceIncludedTax ? $this->getUnitPriceInclTax($item, $storeId, $orderedQuantity)
            : $this->getUnitPriceExclTax($item, $storeId, $orderedQuantity);
    }

    /**
     * @param OrderItemInterface $item
     * @param $storeId
     * @param float $orderedQuantity
     * @return float
     */
    public function getUnitPriceExclTax(OrderItemInterface $item, $storeId, float $orderedQuantity): float
    {
        if ($this->config->useBaseCurrency($storeId)) {
            return ($item->getBasePrice() - ($item->getBaseDiscountAmount() / $orderedQuantity))
                   + ($item->getBaseDiscountTaxCompensationAmount() / $orderedQuantity);
        }

        return ($item->getPrice() - ($item->getDiscountAmount() / $orderedQuantity))
               + ($item->getDiscountTaxCompensationAmount() / $orderedQuantity);
    }

    /**
     * @param OrderItemInterface $item
     * @param $storeId
     * @param float $orderedQuantity
     * @return float
     */
    public function getUnitPriceInclTax(OrderItemInterface $item, $storeId, float $orderedQuantity): float
    {
        $addedTaxPercentage = 1 + ($item->getTaxPercent() / 100);

        if ($this->config->useBaseCurrency($storeId)) {
            return (($item->getBaseRowTotalInclTax() - $item->getBaseDiscountAmount()) /
                    $orderedQuantity / ($addedTaxPercentage));
        }

        return (($item->getRowTotalInclTax() - $item->getDiscountAmount()) /
                $orderedQuantity / ($addedTaxPercentage));
    }

    /**
     * @param OrderInterface $order
     * @return float
     */
    public function getShippingUnitPrice(OrderInterface $order): float
    {
        if ($this->config->useBaseCurrency($order->getStoreId())) {
            return (float)$order->getBaseShippingAmount();
        }

        return (float)$order->getShippingAmount();
    }
}
