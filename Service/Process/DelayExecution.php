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

use MultiSafepay\Api\Transactions\Transaction;

class DelayExecution
{
    public const TIMEOUT = 1;

    /**
     * Give the first process time to end to avoid saving the order with the wrong status
     *
     * @param string $transactionStatus
     * @return void
     */
    public function execute(string $transactionStatus): void
    {
        if ($transactionStatus === Transaction::COMPLETED) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            sleep(self::TIMEOUT);
        }
    }
}
