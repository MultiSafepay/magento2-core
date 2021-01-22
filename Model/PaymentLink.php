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

namespace MultiSafepay\ConnectCore\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Payment;

class PaymentLink
{
    /**
     * @param Payment $payment
     * @param string $paymentUrl
     * @throws LocalizedException
     */
    public function addToAdditionalInformation(Payment $payment, string $paymentUrl): void
    {
        if ($payment !== null) {
            $payment->setAdditionalInformation('payment_link', $paymentUrl);
        }
    }
}
