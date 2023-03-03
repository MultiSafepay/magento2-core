<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * See DISCLAIMER.md for disclaimer details.
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Api;

use Magento\Sales\Api\Data\OrderInterface;

interface StockReducerInterface
{
    /**
     * @param OrderInterface $order
     */
    public function reduce(OrderInterface $order): void;
}
