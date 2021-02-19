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

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use MultiSafepay\ConnectCore\Config\Config;
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
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        AssetRepository $assetRepository,
        Config $config,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($assetRepository, $config);
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
