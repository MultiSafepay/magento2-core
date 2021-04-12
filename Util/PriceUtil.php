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

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use MultiSafepay\ConnectCore\Config\Config;

class PriceUtil
{
    /**
     * @var Config
     */
    private $config;

    /**
     * GrandTotalUtil constructor.
     *
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
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
        $orderedQuantity = $item->getQtyOrdered();

        if ($this->config->useBaseCurrency($storeId)) {
            return ($item->getBasePrice() - ($item->getBaseDiscountAmount() / $orderedQuantity))
                   + ($item->getBaseDiscountTaxCompensationAmount() / $orderedQuantity);
        }

        return ($item->getPrice() - ($item->getDiscountAmount() / $orderedQuantity))
               + ($item->getDiscountTaxCompensationAmount() / $orderedQuantity);
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
