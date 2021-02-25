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

namespace MultiSafepay\ConnectCore\Plugin\Payment\Helper;

use MultiSafepay\ConnectCore\Model\Ui\Gateway\GenericGatewayConfigProvider;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\MethodInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class DataPlugin
{
    /**
     * @var GenericGatewayConfigProvider
     */
    private $genericGatewayConfigProvider;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * DataPlugin constructor.
     *
     * @param GenericGatewayConfigProvider $genericGatewayConfigProvider
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        GenericGatewayConfigProvider $genericGatewayConfigProvider,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->genericGatewayConfigProvider = $genericGatewayConfigProvider;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param Data $subject
     * @param callable $proceed
     * @param string $code
     * @return MethodInterface
     */
    public function aroundGetMethodInstance(Data $subject, callable $proceed, string $code): MethodInterface
    {
        if (strpos($code, GenericGatewayConfigProvider::CODE) !== false) {
            $result = $proceed(GenericGatewayConfigProvider::CODE);
            $result->initGeneric($code, GenericGatewayConfigProvider::CODE);

            return $result;
        }

        return $proceed($code);
    }

    /**
     * @param Data $subject
     * @param array $result
     * @return array
     */
    public function afterGetPaymentMethods(Data $subject, array $result): array
    {
        if (isset($result[GenericGatewayConfigProvider::CODE])) {
            $genericList = $this->genericGatewayConfigProvider->getGenericGatewaysList();
            $genericData = $result[GenericGatewayConfigProvider::CODE];

            foreach ($genericList as $item) {
                $result[$item] = $genericData;
            }

            unset($result[GenericGatewayConfigProvider::CODE]);
        }

        return $result;
    }
}
