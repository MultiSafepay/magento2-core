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

namespace MultiSafepay\ConnectCore\Plugin\Sales\Model;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Service\Order\CancelMultisafepayOrderPaymentLink;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;

class OrderPlugin
{
    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var CancelMultisafepayOrderPaymentLink
     */
    private $cancelMultisafepayOrderPaymentLink;

    /**
     * @var Config
     */
    private $config;

    /**
     * OrderPlugin constructor.
     *
     * @param PaymentMethodUtil $paymentMethodUtil
     * @param CancelMultisafepayOrderPaymentLink $cancelMultisafepayOrderPaymentLink
     * @param Config $config
     */
    public function __construct(
        PaymentMethodUtil $paymentMethodUtil,
        CancelMultisafepayOrderPaymentLink $cancelMultisafepayOrderPaymentLink,
        Config $config
    ) {
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->cancelMultisafepayOrderPaymentLink = $cancelMultisafepayOrderPaymentLink;
        $this->config = $config;
    }

    /**
     * @param Order $subject
     * @return array
     * @throws LocalizedException
     * @throws Exception
     */
    public function beforeCancel(Order $subject): array
    {
        if ($this->config->getCancelPaymentLinkOption($subject->getStoreId())
            !== CancelMultisafepayOrderPaymentLink::CANCEL_ALWAYS_PRETRANSACTION_OPTION
        ) {
            return [$subject];
        }

        if ($subject->canCancel()
            && $this->paymentMethodUtil->isMultisafepayOrder($subject)
            && $subject->getState() === Order::STATE_PENDING_PAYMENT
        ) {
            $this->cancelMultisafepayOrderPaymentLink->execute($subject);
        }

        return [$subject];
    }
}
