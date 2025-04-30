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

namespace MultiSafepay\ConnectCore\Model\Ui\Gateway;

use MultiSafepay\Api\Account\Account;
use MultiSafepay\ConnectCore\Model\Ui\GenericConfigProvider;

class GooglePayConfigProvider extends GenericConfigProvider
{
    public const CODE = 'multisafepay_googlepay';
    public const GOOGLE_PAY_BUTTON_CONFIG_PATH = 'direct_button';
    public const GOOGLE_PAY_BUTTON_MODE_CONFIG_PATH = 'direct_button_mode';
    public const GOOGLE_PAY_BUTTON_MERCHANT_NAME_CONFIG_PATH = 'direct_button_merchant_name';
    public const GOOGLE_PAY_BUTTON_MERCHANT_ID_CONFIG_PATH = 'direct_button_merchant_id';
    public const GOOGLE_PAY_BUTTON_ID = 'multisafepay-google-pay-button';
    public const GOOGLE_PAY_PRODUCTION_MODE = 'PRODUCTION';
    public const GOOGLE_PAY_TEST_MODE = 'TEST';

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isGooglePayActive(?int $storeId = null): bool
    {
        return (bool)$this->getPaymentConfig($storeId)[self::GOOGLE_PAY_BUTTON_CONFIG_PATH]
               && (bool)$this->getPaymentConfig($storeId)['active'];
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getGooglePayMode(?int $storeId = null): string
    {
        return (bool)$this->getPaymentConfig($storeId)[self::GOOGLE_PAY_BUTTON_MODE_CONFIG_PATH]
            ? self::GOOGLE_PAY_PRODUCTION_MODE : self::GOOGLE_PAY_TEST_MODE;
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getMultisafepayAccountId(?int $storeId = null): string
    {
        return $this->isGooglePayActive($storeId)
            ? (string)($this->getAccountData($storeId)[Account::ACCOUNT_ID_PARAM_NAME] ?? '')
            : '';
    }

    /**
     * @param int|null $storeId
     * @return string[]
     */
    public function getGooglePayMerchantInfo(?int $storeId = null): array
    {
        if (!$this->isGooglePayActive($storeId)) {
            return [
                'merchantName' => '',
                'merchantId' => '',
            ];
        }

        return [
            'merchantName' => (string)(
                $this->getPaymentConfig($storeId)[self::GOOGLE_PAY_BUTTON_MERCHANT_NAME_CONFIG_PATH] ?? ''
            ),
            'merchantId' => (string)(
                $this->getPaymentConfig($storeId)[self::GOOGLE_PAY_BUTTON_MERCHANT_ID_CONFIG_PATH] ?? ''
            ),
        ];
    }
}
