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

use MultiSafepay\Api\Transactions\CaptureRequest;
use MultiSafepay\Api\Transactions\Transaction as TransactionStatus;
use MultiSafepay\Api\Transactions\TransactionResponse as Transaction;

class CaptureUtil
{
    /**
     * @param Transaction $transaction
     * @param float $amount
     * @return bool
     */
    public function isManualCapturePossibleForAmount(Transaction $transaction, float $amount): bool
    {
        $paymentDetails = $transaction->getPaymentDetails();

        return $this->isCaptureManualPayment($transaction) && $paymentDetails->getCaptureRemain()
            && (float)$paymentDetails->getCaptureRemain() >= $amount;
    }

    /**
     * @param Transaction $transaction
     * @return bool
     */
    public function isCaptureManualPayment(Transaction $transaction): bool
    {
        $paymentDetails = $transaction->getPaymentDetails();

        return $transaction->getFinancialStatus() === TransactionStatus::INITIALIZED && $paymentDetails->getCapture()
               && $paymentDetails->getCapture() === CaptureRequest::CAPTURE_MANUAL_TYPE;
    }
}
