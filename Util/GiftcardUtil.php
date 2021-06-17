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

class GiftcardUtil
{
    public const MULTISAFEPAY_GIFTCARD_PAYMENT_TYPE = 'COUPON';
    public const MULTISAFEPAY_GIFTCARD_PAYMENT_ADDITIONAL_DATA_PARAM_NAME = 'multisafepay_coupon_data';

    /**
     * @param array $transaction
     * @return array
     */
    public function getGiftcardPaymentDataFromTransaction(array $transaction): array
    {
        $transactionPaymentMethods = $transaction['payment_methods'] ?? [];
        $result = [];

        foreach ($transactionPaymentMethods as $paymentMethod) {
            if (($paymentMethod['type'] ?? '') === self::MULTISAFEPAY_GIFTCARD_PAYMENT_TYPE) {
                $result[] = $paymentMethod;
            }
        }

        return $result;
    }

    /**
     * @param array $transaction
     * @return bool
     */
    public function isFullGiftcardTransaction(array $transaction): bool
    {
        return strpos($transaction['payment_details']['type'] ?? '', 'Coupon::') !== false;
    }

    /**
     * @param array $transaction
     * @return string
     */
    public function getGiftcardGatewayCodeFromTransaction(array $transaction): string
    {
        return $transaction['payment_details']['coupon_brand'] ?? '';
    }
}
