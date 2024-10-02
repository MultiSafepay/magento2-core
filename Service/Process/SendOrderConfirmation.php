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
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use MultiSafepay\Api\Transactions\Transaction as TransactionStatus;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\EmailSender;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class SendOrderConfirmation implements ProcessInterface
{
    public const STATUSES = [
        TransactionStatus::INITIALIZED,
        TransactionStatus::UNCLEARED,
        TransactionStatus::COMPLETED
    ];

    /**
     * @var EmailSender
     */
    private $emailSender;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * SendOrderConfirmation constructor
     *
     * @param EmailSender $emailSender
     * @param Logger $logger
     */
    public function __construct(
        EmailSender $emailSender,
        Logger $logger
    ) {
        $this->emailSender = $emailSender;
        $this->logger = $logger;
    }

    /**
     * Send the order confirmation email
     *
     * @param Order $order
     * @param array $transaction
     * @return array
     * @throws Exception
     */
    public function execute(Order $order, array $transaction): array
    {
        $transactionStatus = $transaction['status'] ?? '';

        if ((in_array($transactionStatus, self::STATUSES, true))
            && $this->emailSender->sendOrderConfirmationEmail($order)) {
            $this->logger->logInfoForNotification(
                $order->getIncrementId(),
                'Order confirmation email after transaction has been sent',
                $transaction
            );
        }

        if (($transactionStatus === TransactionStatus::COMPLETED)
            && $this->emailSender->sendOrderConfirmationEmail(
                $order,
                EmailSender::AFTER_PAID_TRANSACTION_EMAIL_TYPE
            )) {
            $this->logger->logInfoForNotification(
                $order->getIncrementId(),
                'Order confirmation email after paid transaction has been sent',
                $transaction
            );
        }

        return [StatusOperationInterface::SUCCESS_PARAMETER => true, self::SAVE_ORDER => false];
    }
}
