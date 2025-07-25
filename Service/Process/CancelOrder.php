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
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\OrderService;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class CancelOrder implements ProcessInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * CancelOrder constructor
     *
     * @param Logger $logger
     * @param OrderService $orderService
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Logger $logger,
        OrderService $orderService,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->logger = $logger;
        $this->orderService = $orderService;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Cancel the order
     *
     * @param Order $order
     * @param array $transaction
     * @return array
     * @throws Exception
     */
    public function execute(Order $order, array $transaction): array
    {
        if ($order->getState() === Order::STATE_CANCELED) {
            $this->logger->logInfoForNotification(
                $order->getIncrementId(),
                'Order already in canceled state, returning early',
                $transaction
            );
            return [StatusOperationInterface::SUCCESS_PARAMETER => true];
        }

        if ($order->getState() === Order::STATE_PAYMENT_REVIEW) {
            $order->setState(Order::STATE_PENDING_PAYMENT);
            $order->setStatus(Order::STATE_PENDING_PAYMENT);

            $this->orderRepository->save($order);

            $this->logger->logInfoForNotification(
                $order->getIncrementId(),
                'Order state and status put to pending_payment from payment_review',
                $transaction
            );
        }

        try {
            $this->orderService->cancel($order->getEntityId());
        } catch (LocalizedException $localizedException) {
            $message = 'Exception occurred when trying to cancel the order';
            $this->logger->logInfoForNotification($order->getIncrementId(), $message, $transaction);
            return [
                StatusOperationInterface::SUCCESS_PARAMETER => false,
                StatusOperationInterface::MESSAGE_PARAMETER => $message
            ];
        }

        /** @var Order $order */
        $order = $this->orderRepository->get($order->getEntityId());

        $transactionStatus = $transaction['status'] ?? 'unknown';

        $order->addCommentToStatusHistory(
            __('Order canceled by MultiSafepay, Transaction status: ') . $transactionStatus .
            __(', Transaction ID: ') . ($transaction['transaction_id'] ?? 'unknown')
        );

        $this->orderRepository->save($order);
        $this->logger->logInfoForNotification($order->getIncrementId(), 'Order has been canceled', $transaction);

        return [StatusOperationInterface::SUCCESS_PARAMETER => true];
    }
}
