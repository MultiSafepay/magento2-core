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
use Magento\Payment\Model\Info;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\CreditCardConfigProvider;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class AddCardPaymentInformation implements ProcessInterface
{
    public const CARD_PAYMENT_ADDITIONAL_DATA_PARAM_NAME = 'multisafepay_card_payment';

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
     * Add card payment information to the order
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
                'Payment could not be found when trying to add card payment information',
                $transaction
            );
            return [StatusOperationInterface::SUCCESS_PARAMETER => true];
        }

        if ($payment->getMethod() !== CreditCardConfigProvider::CODE) {
            $this->logger->logInfoForNotification(
                $order->getIncrementId(),
                'Payment method is not a card payment method',
                $transaction
            );
            return [StatusOperationInterface::SUCCESS_PARAMETER => true];
        }

        if (!isset($transaction['payment_details'])) {
            $this->logger->logInfoForNotification(
                $order->getIncrementId(),
                'Card payment information was not added, payment details not found',
                $transaction
            );

            return [StatusOperationInterface::SUCCESS_PARAMETER => true];
        }

        $payment->setAdditionalInformation(
            self::CARD_PAYMENT_ADDITIONAL_DATA_PARAM_NAME,
            $transaction['payment_details']['type'] ?? 'unknown'
        );

        $this->logger->logInfoForNotification(
            $order->getIncrementId(),
            'Card payment information added to the payment additional information',
            $transaction
        );

        return [StatusOperationInterface::SUCCESS_PARAMETER => true];
    }
}
