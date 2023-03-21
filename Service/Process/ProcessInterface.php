<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * Copyright © 2022 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Service\Process;

use Magento\Sales\Api\Data\OrderInterface;

interface ProcessInterface
{
    public const SAVE_ORDER = 'save_order';

    /**
     * Execute the command
     *
     * @param OrderInterface $order
     * @param array $transaction
     * @return array
     */
    public function execute(OrderInterface $order, array $transaction): array;
}
