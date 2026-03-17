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

namespace MultiSafepay\ConnectCore\Observer\Gateway;

use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote\Payment;
use MultiSafepay\ConnectCore\Util\RedirectTokenUtil;

class RedirectTokenDataAssignObserver extends AbstractDataAssignObserver
{
    public const REDIRECT_TOKEN_PARAM_NAME = 'redirect_token';

    /**
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        $data = $this->readDataArgument($observer);
        $additionalData = (array)$data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        /** @var Payment $payment */
        $payment = $this->readPaymentModelArgument($observer);
        $method = (string)$payment->getMethod();

        if (strpos($method, 'multisafepay') !== 0) {
            return;
        }

        if (empty($additionalData[self::REDIRECT_TOKEN_PARAM_NAME])) {
            return;
        }

        $payment->setAdditionalInformation(
            RedirectTokenUtil::REDIRECT_TOKEN_KEY,
            (string)$additionalData[self::REDIRECT_TOKEN_PARAM_NAME]
        );
    }
}
