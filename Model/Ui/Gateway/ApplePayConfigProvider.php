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

use MultiSafepay\ConnectCore\Model\Ui\GenericConfigProvider;

class ApplePayConfigProvider extends GenericConfigProvider
{
    public const CODE = 'multisafepay_applepay';
    public const APPLE_PAY_BUTTON_CONFIG_PATH = 'direct_button';
    public const APPLE_PAY_BUTTON_ID = 'multisafepay-apple-pay-button';

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isApplePayActive(int $storeId = null): bool
    {
        return (bool)$this->getPaymentConfig($storeId)[self::APPLE_PAY_BUTTON_CONFIG_PATH];
    }
}
