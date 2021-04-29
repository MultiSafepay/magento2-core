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
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\Ui\GenericConfigProvider;

class MaestroConfigProvider extends GenericConfigProvider
{
    public const CODE = 'multisafepay_maestro';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * MaestroConfigProvider constructor.
     *
     * @param AssetRepository $assetRepository
     * @param Config $config
     * @param SdkFactory $sdkFactory
     * @param Session $checkoutSession
     * @param Logger $logger
     * @param ResolverInterface $localeResolver
     * @param ScopeConfigInterface $scopeConfig
     * @param PaymentConfig $paymentConfig
     */
    public function __construct(
        AssetRepository $assetRepository,
        Config $config,
        SdkFactory $sdkFactory,
        Session $checkoutSession,
        Logger $logger,
        ResolverInterface $localeResolver,
        PaymentConfig $paymentConfig,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
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
     * @return string
     */
    public function getMaestroGatewayCode(): string
    {
        $maestroMethodCode = self::CODE;

        return (string)$this->scopeConfig->getValue('payment/' . $maestroMethodCode . '/gateway_code');
    }
}
