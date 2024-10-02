<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * Copyright © 2023 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Service\Process;

use Exception;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\OrderStatusUtil;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class SetOrderProcessingStatus implements ProcessInterface
{
    /**
     * @var OrderStatusUtil
     */
    private $orderStatusUtil;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * SetOrderProcessingStatus constructor
     *
     * @param OrderStatusUtil $orderStatusUtil
     * @param Logger $logger
     */
    public function __construct(
        OrderStatusUtil $orderStatusUtil,
        Logger $logger
    ) {
        $this->orderStatusUtil = $orderStatusUtil;
        $this->logger = $logger;
    }

    /**
     * Set the order processing status
     *
     * @param Order $order
     * @param array $transaction
     * @return array
     * @throws Exception
     */
    public function execute(Order $order, array $transaction): array
    {
        $status = $this->orderStatusUtil->getProcessingStatus($order);
        $defaultStatuses = [Order::STATE_PROCESSING, Order::STATE_COMPLETE];

        if (in_array($status, $defaultStatuses, true) && $order->getStatus() === $status) {
            $this->logger->logInfoForNotification(
                $order->getIncrementId(),
                'Order already has correct status, status not changed',
                $transaction
            );
            return [StatusOperationInterface::SUCCESS_PARAMETER => true];
        }

        $orderUpdateStatusMessage = __('Order status has been changed to: %1', $status)->render();

        $order->setStatus($status);
        $order->addCommentToStatusHistory($orderUpdateStatusMessage);
        $this->logger->logInfoForNotification(
            $order->getIncrementId(),
            $orderUpdateStatusMessage,
            $transaction
        );

        return [StatusOperationInterface::SUCCESS_PARAMETER => true];
    }
}
