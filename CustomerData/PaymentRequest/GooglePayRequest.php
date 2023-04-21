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

namespace MultiSafepay\ConnectCore\CustomerData\PaymentRequest;

use Magento\Quote\Api\Data\CartInterface;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\GooglePayConfigProvider;

class GooglePayRequest
{
    /**
     * @var GooglePayConfigProvider
     */
    private $googlePayConfigProvider;

    /**
     * @param GooglePayConfigProvider $googlePayConfigProvider
     */
    public function __construct(
        GooglePayConfigProvider $googlePayConfigProvider
    ) {
        $this->googlePayConfigProvider = $googlePayConfigProvider;
    }

    /**
     * Create the Apple Pay Direct request data
     *
     * @param CartInterface|null $quote
     * @return array
     */
    public function create(?CartInterface $quote): ?array
    {
        if ($quote === null) {
            return null;
        }

        $storeId = $quote->getStoreId();
        $isActive = $this->googlePayConfigProvider->isGooglePayActive($storeId);

        if (!$isActive) {
            return null;
        }

        return [
            'isActive' => $this->googlePayConfigProvider->isGooglePayActive($storeId),
            'googlePayButtonId' => GooglePayConfigProvider::GOOGLE_PAY_BUTTON_ID,
            'mode' => $this->googlePayConfigProvider->getGooglePayMode($storeId),
            'accountId' => $this->googlePayConfigProvider->getMultisafepayAccountId($storeId),
            'merchantInfo' => $this->googlePayConfigProvider->getGooglePayMerchantInfo($storeId),
        ];
    }
}
