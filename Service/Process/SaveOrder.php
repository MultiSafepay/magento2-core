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

namespace MultiSafepay\ConnectCore\Service\Process;

use Exception;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class SaveOrder implements ProcessInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param OrderRepository $orderRepository
     * @param Logger $logger
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Logger $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    /**
     * Save the order
     *
     * @param Order $order
     * @param array $transaction
     * @return array
     * @throws Exception
     */
    public function execute(Order $order, array $transaction): array
    {
        $orderId = $order->getIncrementId();

        try {
            $this->orderRepository->save($order);
        } catch (InputException | NoSuchEntityException | AlreadyExistsException $exception) {
            $this->logger->logNotificationException($orderId, $transaction, $exception);
            $message = 'Exception occurred when saving the order, please check the logs';
            return [
                StatusOperationInterface::SUCCESS_PARAMETER => false,
                StatusOperationInterface::MESSAGE_PARAMETER => $message
            ];
        }

        $this->logger->logInfoForNotification($orderId, 'Order has been saved.', $transaction);

        return [StatusOperationInterface::SUCCESS_PARAMETER => true];
    }
}
