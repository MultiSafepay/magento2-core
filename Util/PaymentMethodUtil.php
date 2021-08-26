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

use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Payment\Model\MethodInterface;

class PaymentMethodUtil
{
    public const MULTISAFEPAY_METHOD_ID = 'is_multisafepay';

    /**
     * @param CartInterface $cart
     * @return bool
     */
    public function isMultisafepayCart(CartInterface $cart): bool
    {
        return $this->checkIsMultisafepayMethodByPayment($cart->getPayment()->getMethodInstance());
    }

    /**
     * @param OrderInterface $order
     * @return bool
     */
    public function isMultisafepayOrder(OrderInterface $order): bool
    {
        return $this->checkIsMultisafepayMethodByPayment($order->getPayment()->getMethodInstance());
    }

    /**
     * @param string $code
     * @return bool
     */
    public function isMultisafepayPaymentByCode(string $code): bool
    {
        $multisafepayPrefix = str_replace('is_', '', self::MULTISAFEPAY_METHOD_ID);

        return !(strpos($code, $multisafepayPrefix) === false);
    }

    /**
     * @param MethodInterface $paymentInstance
     * @return bool
     */
    public function checkIsMultisafepayMethodByPayment(MethodInterface $paymentInstance): bool
    {
        return (bool)$paymentInstance->getConfigData(self::MULTISAFEPAY_METHOD_ID);
    }
}
