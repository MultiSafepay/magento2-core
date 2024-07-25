<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * Copyright © MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Service\Transaction\StatusOperation;

use Exception;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use MultiSafepay\ConnectCore\Service\Process\CancelOrder;
use MultiSafepay\ConnectCore\Service\Process\LogTransactionStatus;
use MultiSafepay\ConnectCore\Service\Process\SaveOrder;
use MultiSafepay\ConnectCore\Service\Process\UpdateOrderStatus;
use MultiSafepay\ConnectCore\Util\ProcessUtil;

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
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var ProcessUtil
     */
    private $processUtil;

    /**
     * @param LogTransactionStatus $logTransactionStatus
     * @param UpdateOrderStatus $updateOrderStatus
     * @param CancelOrder $cancelOrder
     * @param SaveOrder $saveOrder
     * @param OrderRepositoryInterface $orderRepository
     * @param ProcessUtil $processUtil
     */
    public function __construct(
        LogTransactionStatus $logTransactionStatus,
        UpdateOrderStatus $updateOrderStatus,
        CancelOrder $cancelOrder,
        SaveOrder $saveOrder,
        OrderRepositoryInterface $orderRepository,
        ProcessUtil $processUtil
    ) {
        $this->logTransactionStatus = $logTransactionStatus;
        $this->updateOrderStatus = $updateOrderStatus;
        $this->cancelOrder = $cancelOrder;
        $this->saveOrder = $saveOrder;
        $this->orderRepository = $orderRepository;
        $this->processUtil = $processUtil;
    }
    /**
     * Execute the canceled status operation
     *
     * @param OrderInterface $order
     * @param array $transaction
     * @return array
     * @throws Exception
     */
    public function execute(OrderInterface $order, array $transaction): array
    {
        $processes = [
            $this->logTransactionStatus,
            $this->cancelOrder
        ];

        $response = $this->processUtil->executeProcesses($processes, $order, $transaction);

        if (!$response[StatusOperationInterface::SUCCESS_PARAMETER]) {
            return $response;
        }

        $order = $this->orderRepository->get($order->getEntityId());

        $processes = [
            $this->updateOrderStatus,
            $this->saveOrder
        ];

        return $this->processUtil->executeProcesses($processes, $order, $transaction);
    }
}
