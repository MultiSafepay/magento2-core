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
use MultiSafepay\ConnectCore\Config\Config;

class TaxUtil
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
    public function getShippingTaxRate(OrderInterface $order): float
    {
        if ($this->config->useBaseCurrency($order->getStoreId())) {
            $shippingTaxAmount = $order->getBaseShippingTaxAmount();
            $originalShippingAmount = $order->getBaseShippingInclTax() - $shippingTaxAmount;

            return (float) $shippingTaxAmount / $originalShippingAmount * 100;
        }

        $shippingTaxAmount = $order->getShippingTaxAmount();
        $originalShippingAmount = $order->getShippingInclTax() - $shippingTaxAmount;

        return (float) $shippingTaxAmount / $originalShippingAmount * 100;
    }
}
