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
 * Copyright © 2022 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Service\Process;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\SecondChance;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class ReopenOrder implements ProcessInterface
{
    /**
     * @var SecondChance
     */
    private $secondChance;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param SecondChance $secondChance
     * @param Logger $logger
     */
    public function __construct(
        SecondChance $secondChance,
        Logger $logger
    ) {
        $this->secondChance = $secondChance;
        $this->logger = $logger;
    }

    /**
     * Reopen the order
     *
     * @param Order $order
     * @param array $transaction
     * @return array
     * @throws Exception
     */
    public function execute(Order $order, array $transaction): array
    {
        $orderId = $order->getIncrementId();

        if ($order->getState() === Order::STATE_CANCELED) {
            try {
                $this->secondChance->reopenOrder($order);
            } catch (LocalizedException $localizedException) {
                $this->logger->logNotificationException($orderId, $transaction, $localizedException);
                $message = 'Failed to reopen the order. Exception message: ' . $localizedException->getMessage();

                return [
                    StatusOperationInterface::SUCCESS_PARAMETER => false,
                    StatusOperationInterface::MESSAGE_PARAMETER => $message
                ];
            }

            $this->logger->logInfoForNotification($orderId, 'The order has been reopened.', $transaction);
        }

        return [StatusOperationInterface::SUCCESS_PARAMETER => true];
    }
}
