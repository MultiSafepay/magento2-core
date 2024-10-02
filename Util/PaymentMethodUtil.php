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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;

class PaymentMethodUtil
{
    public const MULTISAFEPAY_METHOD_ID = 'is_multisafepay';

    /**
     * @param Quote $cart
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function isMultisafepayCart(Quote $cart): bool
    {
        return $this->checkIsMultisafepayMethodByPayment($cart->getPayment()->getMethodInstance());
    }

    /**
     * Check if it is a MultiSafepay order
     *
     * @param Order $order
     * @return bool
     * @throws LocalizedException
     */
    public function isMultisafepayOrder(Order $order): bool
    {
        if ($order->getPayment() === null) {
            return false;
        }

        /** @var Payment $payment */
        $payment = $order->getPayment();

        return $this->checkIsMultisafepayMethodByPayment($payment->getMethodInstance());
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
