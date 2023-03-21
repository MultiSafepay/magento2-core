<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * See DISCLAIMER.md for disclaimer details.
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Service\Process;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\GiftcardUtil;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class ChangePaymentMethod implements ProcessInterface
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
     * @param Logger $logger
     * @param Config $config
     * @param GiftcardUtil $giftcardUtil
     * @param PaymentMethodUtil $paymentMethodUtil
     */
    public function __construct(
        Logger $logger,
        Config $config,
        GiftcardUtil $giftcardUtil,
        PaymentMethodUtil $paymentMethodUtil
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->giftcardUtil = $giftcardUtil;
        $this->paymentMethodUtil = $paymentMethodUtil;
    }

    /**
     * Execute the process to change the payment method
     *
     * @param OrderInterface $order
     * @param array $transaction
     * @return array
     * @throws Exception
     */
    public function execute(OrderInterface $order, array $transaction): array
    {
        $orderId = $order->getIncrementId();
        $payment = $order->getPayment();

        if ($payment === null) {
            $message = 'Payment method could not be changed, because the payment was not found';
            $this->logger->logInfoForNotification($orderId, $message, $transaction);
            return [
                StatusOperationInterface::SUCCESS_PARAMETER => false,
                StatusOperationInterface::MESSAGE_PARAMETER => $message
            ];
        }

        $gatewayCode = (string)$payment->getMethodInstance()->getConfigData('gateway_code');

        $this->logger->logInfoForNotification(
            $orderId,
            'MultiSafepay change payment process has been started',
            $transaction
        );

        $transactionType = $transaction['payment_details']['type'] ?? '';

        if ($this->canChangePaymentMethod($transactionType, $gatewayCode, $order)) {
            if ($this->giftcardUtil->isFullGiftcardTransaction($transaction)) {
                $transactionType = $this->giftcardUtil->getGiftcardGatewayCodeFromTransaction($transaction) ?:
                    $transactionType;
            }

            $this->changePaymentMethod($order, $payment, $transaction, $transactionType);
        }

        $this->logger->logInfoForNotification(
            $orderId,
            'MultiSafepay change payment process has ended',
            $transaction
        );

        return [StatusOperationInterface::SUCCESS_PARAMETER => true];
    }

    /**
     * Check if the payment method can be changed
     *
     * @param string $transactionType
     * @param string $gatewayCode
     * @param OrderInterface $order
     * @return bool
     * @throws LocalizedException
     */
    private function canChangePaymentMethod(string $transactionType, string $gatewayCode, OrderInterface $order): bool
    {
        /**
         * In case Vault is being used, we want the credit card gateway to remain the same, so that Magento can process
         * Vault as a credit card gateway, since that is also the gateway which the token was saved with
         */
        if ($gatewayCode === 'CREDITCARD') {
            $disallowedTransactionTypes = [
                'AMEX',
                'VISA',
                'MAESTRO',
                'MASTERCARD'
            ];

            if (in_array($transactionType, $disallowedTransactionTypes)) {
                return false;
            }
        }

        return $transactionType && $transactionType !== $gatewayCode
               && $this->paymentMethodUtil->isMultisafepayOrder($order);
    }

    /**
     * Change the payment method if needed
     *
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param array $transaction
     * @param string $transactionType
     * @throws Exception
     */
    private function changePaymentMethod(
        OrderInterface $order,
        OrderPaymentInterface $payment,
        array $transaction,
        string $transactionType
    ): void {
        $methodList = $this->config->getValueByPath('payment');

        foreach ($methodList as $code => $method) {
            if (isset($method['gateway_code']) && $method['gateway_code'] === $transactionType
                && strpos($code, '_recurring') === false) {
                $payment->setMethod($code);
                $logMessage = __('Payment method changed to ') . $transactionType;
                $order->addCommentToStatusHistory($logMessage);
                $this->logger->logInfoForNotification($order->getIncrementId(), $logMessage, $transaction);

                return;
            }
        }
    }
}
