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

namespace MultiSafepay\ConnectCore\Service\Transaction\StatusOperation;

use Magento\Sales\Api\Data\OrderInterface;
use MultiSafepay\ConnectCore\Service\Process\CancelOrder;
use MultiSafepay\ConnectCore\Service\Process\LogTransactionStatus;
use MultiSafepay\ConnectCore\Service\Process\ProcessInterface;
use MultiSafepay\ConnectCore\Service\Process\SaveOrder;
use MultiSafepay\ConnectCore\Service\Process\UpdateOrderStatus;

class CanceledStatusOperation implements StatusOperationInterface
{
    /**
     * @var LogTransactionStatus
     */
    private $logTransactionStatus;

    /**
     * @var UpdateOrderStatus
     */
    private $updateOrderStatus;

    /**
     * @var CancelOrder
     */
    private $cancelOrder;

    /**
     * @var SaveOrder
     */
    private $saveOrder;

    /**
     * CanceledStatusOperation constructor
     *
     * @param LogTransactionStatus $logTransactionStatus
     * @param UpdateOrderStatus $updateOrderStatus
     * @param CancelOrder $cancelOrder
     * @param SaveOrder $saveOrder
     */
    public function __construct(
        LogTransactionStatus $logTransactionStatus,
        UpdateOrderStatus $updateOrderStatus,
        CancelOrder $cancelOrder,
        SaveOrder $saveOrder
    ) {
        $this->logTransactionStatus = $logTransactionStatus;
        $this->updateOrderStatus = $updateOrderStatus;
        $this->cancelOrder = $cancelOrder;
        $this->saveOrder = $saveOrder;
    }

    /**
     * Execute the canceled status operation
     *
     * @param OrderInterface $order
     * @param array $transaction
     * @return array
     */
    public function execute(OrderInterface $order, array $transaction): array
    {
        $processes = [
            $this->logTransactionStatus,
            $this->cancelOrder,
            $this->updateOrderStatus,
            $this->saveOrder
        ];

        /** @var ProcessInterface $process */
        foreach ($processes as $process) {
            $response = $process->execute($order, $transaction);

            if (!$response[self::SUCCESS_PARAMETER]) {
                return $response;
            }
        }

        return [self::SUCCESS_PARAMETER => true];
    }
}
