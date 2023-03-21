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

namespace MultiSafepay\ConnectCore\Service\Transaction\StatusOperation;

use Magento\Sales\Api\Data\OrderInterface;

interface StatusOperationInterface
{
    public const SUCCESS_PARAMETER = 'success';
    public const MESSAGE_PARAMETER = 'message';
    
    /**
     * Executes the processes which are needed to fulfill the status notification
     *
     * @param OrderInterface $order
     * @param array $transaction
     *
     * @return array Returns an array which acts as a response containing a success and an optional message
     * Examples:
     * return ['success' => false, 'message' => 'something went wrong'];
     * return ['success' => true];
     */
    public function execute(OrderInterface $order, array $transaction): array;
}
