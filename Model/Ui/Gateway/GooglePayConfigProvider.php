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

namespace MultiSafepay\ConnectCore\Model\Ui\Gateway;

use Magento\Checkout\Model\Session;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Store\Model\StoreManagerInterface;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\Ui\GenericConfigProvider;

class GooglePayConfigProvider extends GenericConfigProvider
{
    public const CODE = 'multisafepay_googlepay';
    public const GOOGLE_PAY_BUTTON_CONFIG_PATH = 'direct_button';
    public const GOOGLE_PAY_BUTTON_MODE_CONFIG_PATH = 'direct_button_mode';
    public const GOOGLE_PAY_BUTTON_ACCOUNT_ID_CONFIG_PATH = 'direct_button_account_id';
    public const GOOGLE_PAY_BUTTON_MERCHANT_NAME_CONFIG_PATH = 'direct_button_merchant_name';
    public const GOOGLE_PAY_BUTTON_MERCHANT_ID_CONFIG_PATH = 'direct_button_merchant_id';
    public const GOOGLE_PAY_BUTTON_ID = 'multisafepay-google-pay-button';
    public const GOOGLE_PAY_PRODUCTION_MODE = 'PRODUCTION';
    public const GOOGLE_PAY_TEST_MODE = 'TEST';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * ApplePayConfigProvider constructor.
     *
     * @param AssetRepository $assetRepository
     * @param Config $config
     * @param SdkFactory $sdkFactory
     * @param Session $checkoutSession
     * @param Logger $logger
     * @param ResolverInterface $localeResolver
     * @param PaymentConfig $paymentConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        AssetRepository $assetRepository,
        Config $config,
        SdkFactory $sdkFactory,
        Session $checkoutSession,
        Logger $logger,
        ResolverInterface $localeResolver,
        PaymentConfig $paymentConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
        parent::__construct(
            $assetRepository,
            $config,
            $sdkFactory,
            $checkoutSession,
            $logger,
            $localeResolver,
            $paymentConfig
        );
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isGooglePayActive(int $storeId = null): bool
    {
        return (bool)$this->getPaymentConfig($storeId)[self::GOOGLE_PAY_BUTTON_CONFIG_PATH];
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getGooglePayMode(int $storeId = null): string
    {
        return (bool)$this->getPaymentConfig($storeId)[self::GOOGLE_PAY_BUTTON_MODE_CONFIG_PATH]
            ? self::GOOGLE_PAY_PRODUCTION_MODE : self::GOOGLE_PAY_TEST_MODE;
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getMultisafepayAccountId(int $storeId = null): string
    {
        return (string)$this->getPaymentConfig($storeId)[self::GOOGLE_PAY_BUTTON_ACCOUNT_ID_CONFIG_PATH];
    }

    /**
     * @param int|null $storeId
     * @return string[]
     */
    public function getGooglePayMerchantInfo(int $storeId = null): array
    {
        return [
            'merchantName' => (string)$this->getPaymentConfig($storeId)[self::GOOGLE_PAY_BUTTON_MERCHANT_NAME_CONFIG_PATH],
            'merchantId' => (string)$this->getPaymentConfig($storeId)[self::GOOGLE_PAY_BUTTON_MERCHANT_ID_CONFIG_PATH]
        ];
    }
}
