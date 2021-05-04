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

namespace MultiSafepay\ConnectCore\Util;

use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\Api\Transactions\CaptureRequest;
use MultiSafepay\Api\Transactions\Transaction as TransactionStatus;
use MultiSafepay\Api\Transactions\TransactionResponse as Transaction;
use MultiSafepay\ConnectAdminhtml\Model\Config\Source\PaymentAction;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\MaestroConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\MastercardConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\VisaConfigProvider;

class CaptureUtil
{
    public const AVAILABLE_MANUAL_CAPTURE_METHODS = [
        VisaConfigProvider::CODE,
        MastercardConfigProvider::CODE,
        MaestroConfigProvider::CODE,
    ];

    /**
     * @param Transaction $transaction
     * @param float $amount
     * @return bool
     */
    public function isManualCapturePossibleForAmount(Transaction $transaction, float $amount): bool
    {
        $paymentDetails = $transaction->getPaymentDetails();

        return $this->isCaptureManualTransaction($transaction) && $paymentDetails->getCaptureRemain()
               && (float)$paymentDetails->getCaptureRemain() >= $amount;
    }

    /**
     * @param Transaction $transaction
     * @return bool
     */
    public function isCaptureManualTransaction(Transaction $transaction): bool
    {
        $paymentDetails = $transaction->getPaymentDetails();

        return $transaction->getFinancialStatus() === TransactionStatus::INITIALIZED && $paymentDetails->getCapture()
               && $paymentDetails->getCapture() === CaptureRequest::CAPTURE_MANUAL_TYPE;
    }

    /**
     * @param OrderPaymentInterface $payment
     * @return bool
     */
    public function isCaptureManualPayment(OrderPaymentInterface $payment): bool
    {
        return in_array($payment->getMethod(), self::AVAILABLE_MANUAL_CAPTURE_METHODS)
               && $payment->getMethodInstance()->getConfigPaymentAction()
                  === PaymentAction::PAYMENT_ACTION_AUTHORIZE_ONLY;
    }
}
