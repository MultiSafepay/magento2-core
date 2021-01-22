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

namespace MultiSafepay\ConnectCore\Model;

use Magento\Sales\Api\Data\OrderInterface;
use MultiSafepay\ConnectCore\Api\StockReducerInterface;

class StockReducer implements StockReducerInterface
{
    /**
     * @param OrderInterface $order
     */
    public function reduce(OrderInterface $order): void  // phpcs:ignore
    {
        /*
         * This module is implemented in one of the following modules:
         * - MultiSafepay_ConnectMSI
         * - MultiSafepay_ConnectCatalogInventory
         */
    }
}
