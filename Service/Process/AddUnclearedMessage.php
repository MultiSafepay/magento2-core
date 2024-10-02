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
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class AddUnclearedMessage implements ProcessInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Add order comment and log for uncleared transaction
     *
     * @param Order $order
     * @param array $transaction
     * @return array
     * @throws Exception
     */
    public function execute(Order $order, array $transaction): array
    {
        /** @var Payment $payment */
        $payment = $order->getPayment();

        $orderId = $order->getIncrementId();

        if ($payment === null) {
            $message = 'Uncleared message could not be added, because the payment was not found';
            $this->logger->logInfoForNotification($orderId, $message, $transaction);
            return [
                StatusOperationInterface::SUCCESS_PARAMETER => false,
                StatusOperationInterface::MESSAGE_PARAMETER => $message
            ];
        }

        try {
            $gatewayCode = (string)$payment->getMethodInstance()->getConfigData('gateway_code');
        } catch (LocalizedException $localizedException) {
            $this->logger->logNotificationException($orderId, $transaction, $localizedException);
            $message = 'Uncleared message could not be added, because the gateway code was not found';

            return [
                StatusOperationInterface::SUCCESS_PARAMETER => false,
                StatusOperationInterface::MESSAGE_PARAMETER => $message
            ];
        }

        if ($gatewayCode !== 'SANTANDER') {
            $message = __('Uncleared Transaction. You can accept the transaction manually in MultiSafepay Control');
            $order->addCommentToStatusHistory($message);
            $this->logger->logInfoForNotification($orderId, $message->render(), $transaction);
        }

        return [StatusOperationInterface::SUCCESS_PARAMETER => true];
    }
}
