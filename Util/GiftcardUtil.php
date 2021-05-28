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

use MultiSafepay\Api\Transactions\TransactionResponse as Transaction;

class GiftcardUtil
{
    public const MULTISAFEPAY_GIFTCARD_PAYMENT_TYPE = 'COUPON';
    public const MULTISAFEPAY_GIFTCARD_PAYMENT_ADDITIONAL_DATA_PARAM_NAME = 'multisafepay_coupon_data';

    /**
     * @param Transaction $transaction
     * @return array
     */
    public function getGiftcardPaymentDataFromTransaction(Transaction $transaction): array
    {
        $transactionPaymentMethods = $transaction->getPaymentMethods();
        $result = [];

        foreach ($transactionPaymentMethods as $paymentMethod) {
            if ($paymentMethod->getType() === self::MULTISAFEPAY_GIFTCARD_PAYMENT_TYPE) {
                $result[] = $paymentMethod->getData();
            }
        }

        return $result;
    }

    /**
     * @param Transaction $transaction
     * @return bool
     */
    public function isFullGiftcardTransaction(Transaction $transaction): bool
    {
        return strpos($transaction->getPaymentDetails()->getType(), 'Coupon::') !== false;
    }

    /**
     * @param Transaction $transaction
     * @return string
     */
    public function getGiftcardGatewayCodeFromTransaction(Transaction $transaction): string
    {
        $paymentDetails = $transaction->getPaymentDetails()->getData();

        return $paymentDetails['coupon_brand'] ?? '';
    }
}
