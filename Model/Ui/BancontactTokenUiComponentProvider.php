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

namespace MultiSafepay\ConnectCore\Model\Ui;

use MultiSafepay\ConnectCore\Model\Ui\Gateway\BancontactConfigProvider;

class BancontactTokenUiComponentProvider extends VaultTokenUiComponentProvider
{
    /**
     * @return string
     */
    protected function getVaultCode(): string
    {
        return BancontactConfigProvider::VAULT_CODE;
    }
}
