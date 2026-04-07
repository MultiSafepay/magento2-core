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

namespace MultiSafepay\ConnectCore\Service\Process;

use Exception;
use Magento\Payment\Model\Info;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\ApplePayConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\GooglePayConfigProvider;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class AddWalletInformation implements ProcessInterface
{
    public const WALLET_CARD_TYPE_ADDITIONAL_DATA_PARAM_NAME = 'multisafepay_wallet_method';

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
     * Add wallet information to the order
     *
     * @param Order $order
     * @param array $transaction
     * @return array
     * @throws Exception
     */
    public function execute(Order $order, array $transaction): array
    {
        /** @var Info $payment */
        $payment = $order->getPayment();

        if ($payment === null) {
            $this->logger->logInfoForNotification(
                $order->getIncrementId(),
                'Payment could not be found when trying to add wallet information',
                $transaction
            );
            return [StatusOperationInterface::SUCCESS_PARAMETER => true];
        }

        if (!in_array($payment->getMethod(), [GooglePayConfigProvider::CODE, ApplePayConfigProvider::CODE])) {
            $this->logger->logInfoForNotification(
                $order->getIncrementId(),
                'Payment method is not a wallet method, no need to add wallet information',
                $transaction
            );

            return [StatusOperationInterface::SUCCESS_PARAMETER => true];
        }

        if (!isset($transaction['payment_details']) || !is_array($transaction['payment_details'])) {
            $this->logger->logInfoForNotification(
                $order->getIncrementId(),
                'Wallet information was not added, payment details not found',
                $transaction
            );

            return [StatusOperationInterface::SUCCESS_PARAMETER => true];
        }

        $payment->setAdditionalInformation(
            self::WALLET_CARD_TYPE_ADDITIONAL_DATA_PARAM_NAME,
            $transaction['payment_details']['type'] ?? 'unknown'
        );

        $this->logger->logInfoForNotification(
            $order->getIncrementId(),
            'Wallet information added to the payment additional information',
            $transaction
        );

        return [StatusOperationInterface::SUCCESS_PARAMETER => true];
    }
}
