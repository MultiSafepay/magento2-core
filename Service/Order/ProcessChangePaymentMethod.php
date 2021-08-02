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

namespace MultiSafepay\ConnectCore\Service\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\GiftcardUtil;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;

class ProcessChangePaymentMethod
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var GiftcardUtil
     */
    private $giftcardUtil;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var Config
     */
    private $config;

    /**
     * ProcessChangePaymentMethod constructor.
     *
     * @param Logger $logger
     * @param GiftcardUtil $giftcardUtil
     * @param PaymentMethodUtil $paymentMethodUtil
     * @param Config $config
     */
    public function __construct(
        Logger $logger,
        GiftcardUtil $giftcardUtil,
        PaymentMethodUtil $paymentMethodUtil,
        Config $config
    ) {
        $this->logger = $logger;
        $this->giftcardUtil = $giftcardUtil;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->config = $config;
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param string $transactionType
     * @param string $gatewayCode
     * @param array $transaction
     */
    public function execute(
        OrderInterface $order,
        OrderPaymentInterface $payment,
        string $transactionType,
        string $gatewayCode,
        array $transaction
    ): void {
        $orderId = $order->getIncrementId();

        $this->logger->logInfoForOrder(
            $orderId,
            __('MultiSafepay change payment process has been started')->render(),
            Logger::DEBUG
        );

        if ($this->canChangePaymentMethod($transactionType, $gatewayCode, $order)) {
            if ($this->giftcardUtil->isFullGiftcardTransaction($transaction)) {
                $transactionType = $this->giftcardUtil->getGiftcardGatewayCodeFromTransaction($transaction) ?:
                    $transactionType;
            }

            $this->changePaymentMethod($order, $payment, $transactionType);
        }

        $this->logger->logInfoForOrder(
            $orderId,
            __('MultiSafepay change payment process has ended')->render(),
            Logger::DEBUG
        );
    }

    /**
     * @param string $transactionType
     * @param string $gatewayCode
     * @param OrderInterface $order
     * @return bool
     */
    private function canChangePaymentMethod(string $transactionType, string $gatewayCode, OrderInterface $order): bool
    {
        return $transactionType && $transactionType !== $gatewayCode
               && $this->paymentMethodUtil->isMultisafepayOrder($order);
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param string $transactionType
     */
    private function changePaymentMethod(
        OrderInterface $order,
        OrderPaymentInterface $payment,
        string $transactionType
    ): void {
        $methodList = $this->config->getValueByPath('payment');

        foreach ($methodList as $code => $method) {
            if (isset($method['gateway_code']) && $method['gateway_code'] === $transactionType
                && strpos($code, '_recurring') === false) {
                $payment->setMethod($code);
                $logMessage = __('Payment method changed to ') . $transactionType;
                $order->addCommentToStatusHistory($logMessage);
                $this->logger->logInfoForOrder($order->getIncrementId(), $logMessage, Logger::DEBUG);

                return;
            }
        }
    }
}
