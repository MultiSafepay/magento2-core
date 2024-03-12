<?php

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\CustomerBuilder;

use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\CustomerDetails;

class BrowserInfoBuilder
{
    /**
     * Add the browser info to the customer details
     *
     * @param CustomerDetails $customerDetails
     * @param OrderPaymentInterface $payment
     */
    public function build(CustomerDetails $customerDetails, OrderPaymentInterface $payment): void
    {
        $additionalInfo = $payment->getAdditionalInformation();

        if (isset($additionalInfo['browser_info'])) {
            $customerDetails->addData([
                'browser' => [
                    'java_enabled' => (bool)$additionalInfo['browser_info']['java_enabled'] ?? false,
                    'javascript_enabled' => (bool)$additionalInfo['browser_info']['javascript_enabled'] ?? false,
                    'language' => $additionalInfo['browser_info']['language'] ?? '',
                    'screen_color_depth' => $additionalInfo['browser_info']['screen_color_depth'] ?? '',
                    'screen_height' => $additionalInfo['browser_info']['screen_height'] ?? '',
                    'screen_width' => $additionalInfo['browser_info']['screen_width'] ?? '',
                    'timezone' => $additionalInfo['browser_info']['time_zone'] ?? '',
                    'user_agent' => $additionalInfo['browser_info']['user_agent'] ?? '',
                    'cookies_enabled' => (bool)$additionalInfo['browser_info']['cookies_enabled'] ?? false,
                    'platform' => $additionalInfo['browser_info']['platform'] ?? ''
                ]
            ]);
        }
    }
}
