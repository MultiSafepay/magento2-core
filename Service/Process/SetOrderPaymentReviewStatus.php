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

use Exception;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class SetOrderPaymentReviewStatus implements ProcessInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * SetOrderPaymentReviewStatus constructor
     *
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Set the order payment review status
     *
     * @param Order $order
     * @param array $transaction
     * @return array
     * @throws Exception
     */
    public function execute(Order $order, array $transaction): array
    {
        if (in_array($order->getState(), [Order::STATE_PROCESSING, Order::STATE_COMPLETE], true)) {
            $this->logger->logInfoForNotification(
                $order->getIncrementId(),
                'Order already in processing or complete state, can not change back to payment review',
                $transaction
            );
            return [StatusOperationInterface::SUCCESS_PARAMETER => true];
        }

        $order->setState(Order::STATE_PAYMENT_REVIEW);
        $order->setStatus(Order::STATE_PAYMENT_REVIEW);

        $this->logger->logInfoForNotification(
            $order->getIncrementId(),
            'Order status has been changed to: ' . Order::STATE_PAYMENT_REVIEW,
            $transaction
        );

        return [StatusOperationInterface::SUCCESS_PARAMETER => true];
    }
}
