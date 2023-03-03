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

namespace MultiSafepay\ConnectCore\Util;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Store\Model\Store;
use MultiSafepay\Api\Transactions\CaptureRequest;
use MultiSafepay\Api\Transactions\Transaction as TransactionStatus;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\CreditCardConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\MaestroConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\MastercardConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\VisaConfigProvider;

class CaptureUtil
{
    public const PAYMENT_ACTION_AUTHORIZE_ONLY = 'authorize';
    public const PAYMENT_ACTION_AUTHORIZE_AND_CAPTURE = 'initialize';
    public const CAPTURE_TRANSACTION_TYPE_MANUAL = 'manual';
    public const MULTISAFEPAY_CAPTURE_DATA_FIELD_NAME = "multisafepay_capture_data";

    public const AVAILABLE_MANUAL_CAPTURE_METHODS = [
        VisaConfigProvider::CODE,
        VisaConfigProvider::VAULT_CODE,
        CreditCardConfigProvider::CODE,
        CreditCardConfigProvider::VAULT_CODE,
        MastercardConfigProvider::CODE,
        MastercardConfigProvider::VAULT_CODE,
        MaestroConfigProvider::CODE
    ];

    public const AVAILABLE_MANUAL_CAPTURE_CARD_BRANDS = ['VISA', 'MASTERCARD', 'MAESTRO'];

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var Config
     */
    private $config;

    /**
     * CaptureUtil constructor.
     *
     * @param DateTime $dateTime
     * @param Config $config
     */
    public function __construct(
        DateTime $dateTime,
        Config $config
    ) {
        $this->dateTime = $dateTime;
        $this->config = $config;
    }

    /**
     * @param array $transaction
     * @param float $amount
     * @return bool
     */
    public function isManualCapturePossibleForAmount(array $transaction, float $amount): bool
    {
        $paymentDetails = $transaction['payment_details'] ?? [];

        return isset($paymentDetails['capture_remain'])
               && (float)$paymentDetails['capture_remain'] >= round($amount * 100, 10);
    }

    /**
     * @param array $transaction
     * @return bool
     */
    public function isCaptureManualTransaction(array $transaction): bool
    {
        $paymentDetails = $transaction['payment_details'] ?? [];

        return isset($transaction['financial_status']) && isset($paymentDetails['capture'])
               && $transaction['financial_status'] === TransactionStatus::INITIALIZED
               && $paymentDetails['capture'] === CaptureRequest::CAPTURE_MANUAL_TYPE;
    }

    /**
     * @param array $transaction
     * @return bool
     */
    public function isCaptureManualReservationExpired(array $transaction): bool
    {
        $paymentDetails = $transaction['payment_details'] ?? [];

        if (!($reservationExpirationData = $paymentDetails['capture_expiry'] ?? '')) {
            return true;
        }

        return $this->dateTime->timestamp($reservationExpirationData) < $this->dateTime->timestamp();
    }

    /**
     * @param OrderPaymentInterface $payment
     * @return bool
     */
    public function isCaptureManualPayment(OrderPaymentInterface $payment): bool
    {
        $storeId = $payment->getOrder() ? $payment->getOrder()->getStoreId() : Store::DEFAULT_STORE_ID;

        if (!$this->config->isManualCaptureEnabled($storeId)
            || !in_array($payment->getMethod(), self::AVAILABLE_MANUAL_CAPTURE_METHODS, true)
            || !($payment->getMethodInstance()->getConfigPaymentAction() === self::PAYMENT_ACTION_AUTHORIZE_ONLY)
        ) {
            return false;
        }

        if (in_array($payment->getMethod(), [CreditCardConfigProvider::CODE, CreditCardConfigProvider::VAULT_CODE])) {
            //$cardBrand = $payment->getMethod() === CreditCardConfigProvider::VAULT_CODE ? $payment->getType()
            //    : $payment->getAdditionalInformation(CreditCardDataAssignObserver::CREDIT_CARD_BRAND_PARAM_NAME);

            //return $cardBrand && in_array($cardBrand, self::AVAILABLE_MANUAL_CAPTURE_CARD_BRANDS);
            return true;
        }

        return true;
    }

    /**
     * @param string $transactionId
     * @param OrderPaymentInterface $payment
     * @return array|null
     */
    public function getCaptureDataByTransactionId(string $transactionId, OrderPaymentInterface $payment): ?array
    {
        if ($captureData = $payment->getAdditionalInformation(
            self::MULTISAFEPAY_CAPTURE_DATA_FIELD_NAME
        )) {
            foreach ($captureData as $captureDataItem) {
                if (isset($captureDataItem['transaction_id'])
                    && $transactionId === (string)$captureDataItem['transaction_id']
                ) {
                    return $captureDataItem;
                }
            }
        }

        return null;
    }
}
