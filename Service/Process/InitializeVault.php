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
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\Vault;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class InitializeVault implements ProcessInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Vault
     */
    private $vault;

    /**
     * InitializeVault constructor
     *
     * @param Logger $logger
     * @param Vault $vault
     */
    public function __construct(
        Logger $logger,
        Vault $vault
    ) {
        $this->logger = $logger;
        $this->vault = $vault;
    }

    /**
     * Execute the Vault initialization process
     *
     * @param Order $order
     * @param array $transaction
     * @return array
     * @throws Exception
     */
    public function execute(Order $order, array $transaction): array
    {
        $orderId = $order->getIncrementId();

        $this->logger->logInfoForNotification(
            $orderId,
            'MultiSafepay initialize Vault process has been started',
            $transaction
        );

        $payment = $order->getPayment();

        if ($payment === null) {
            $message = 'Payment method could not be changed, because the payment was not found';
            $this->logger->logInfoForNotification($orderId, $message, $transaction);
            return [
                StatusOperationInterface::SUCCESS_PARAMETER => false,
                StatusOperationInterface::MESSAGE_PARAMETER => $message
            ];
        }

        $isVaultInitialized = false;

        try {
            $isVaultInitialized = $this->vault->initialize($payment, $transaction['payment_details'] ?? []);
        } catch (Exception $exception) {
            $this->logger->logNotificationException($orderId, $transaction, $exception);
        }

        if ($isVaultInitialized) {
            $this->logger->logInfoForNotification($orderId, 'Vault has been initialized.', $transaction);
            return [StatusOperationInterface::SUCCESS_PARAMETER => true];
        }

        $this->logger->logInfoForNotification(
            $orderId,
            'MultiSafepay initialize Vault process has ended',
            $transaction
        );

        return [StatusOperationInterface::SUCCESS_PARAMETER => true];
    }
}
