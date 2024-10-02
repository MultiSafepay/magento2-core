<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * Copyright © 2021 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Service\Process;

use Exception;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\OrderStatusUtil;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class UpdateOrderStatus implements ProcessInterface
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
     * Update the status if needed
     *
     * @param Order $order
     * @param array $transaction
     * @return array
     * @throws Exception
     */
    public function execute(Order $order, array $transaction): array
    {
        $saveOrder = false;
        $statusToUpdate = $this->orderStatusUtil->getOrderStatusByTransactionStatus($order, $transaction['status']);

        if ($statusToUpdate) {
            $orderUpdateStatusMessage = __('Order status has been changed to: %1', $statusToUpdate);
            $order->setStatus($statusToUpdate);
            $order->addCommentToStatusHistory($orderUpdateStatusMessage);
            $this->logger->logInfoForOrder(
                $order->getIncrementId(),
                $orderUpdateStatusMessage->render(),
                Logger::DEBUG
            );
            $saveOrder = true;
        }

        return [StatusOperationInterface::SUCCESS_PARAMETER => true, self::SAVE_ORDER => $saveOrder];
    }
}
