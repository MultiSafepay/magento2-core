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
 * Copyright © 2020 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Util;

use Magento\Payment\Gateway\Config\Config;
use MultiSafepay\ConnectCore\Config\Config as CoreConfig;

class CheckoutFieldsUtil
{
    /**
     * @var Config
     */
    private $config;

    /**
     * CheckoutFieldsUtil constructor.
     *
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Returns the selected checkout fields from the payment config
     *
     * @param string $gatewayCode
     * @param int $storeId
     * @return array
     */
    public function getCheckoutFields(string $gatewayCode, int $storeId): array
    {
        $this->config->setMethodCode($gatewayCode);
        $checkoutFields = $this->config->getValue(CoreConfig::CHECKOUT_FIELDS, $storeId);

        if ($checkoutFields) {
            return explode(',', $checkoutFields);
        }

        return [];
    }
}
