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

namespace MultiSafepay\ConnectCore\Plugin\Sales\Model\Order;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\ConnectCore\Util\CaptureUtil;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;

class PaymentPlugin
{
    /**
     * @var CaptureUtil
     */
    private $captureUtil;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * PaymentPlugin constructor.
     *
     * @param CaptureUtil $captureUtil
     * @param PaymentMethodUtil $paymentMethodUtil
     */
    public function __construct(
        CaptureUtil $captureUtil,
        PaymentMethodUtil $paymentMethodUtil
    ) {
        $this->captureUtil = $captureUtil;
        $this->paymentMethodUtil = $paymentMethodUtil;
    }

    /**
     * @param Payment $subject
     * @param bool $result
     * @return bool
     * @throws LocalizedException
     */
    public function afterCanVoid(Payment $subject, bool $result): bool
    {
        $paymentMethodInstance = $subject->getMethodInstance();

        if ($this->paymentMethodUtil->checkIsMultisafepayMethodByPayment($paymentMethodInstance)) {
            return $paymentMethodInstance->canVoid() && $this->captureUtil->isManualCaptureEnabled($subject);
        }

        return $result;
    }
}
