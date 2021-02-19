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

class AmountUtil
{
    /**
     * @var Config
     */
    private $config;

    /**
     * AmountUtil constructor.
     *
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Returns the amount based on the base to order rate when not using base currency
     *
     * @param float $amount
     * @param OrderInterface $order
     * @return float
     */
    public function getAmount(float $amount, OrderInterface $order): float
    {
        if ($this->config->useBaseCurrency($order->getStoreId())) {
            return $amount;
        }

        return round($amount * $order->getBaseToOrderRate(), 2);
    }
}
