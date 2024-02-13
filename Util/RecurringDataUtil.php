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

class RecurringDataUtil
{
    /**
     * @var VaultUtil
     */
    private $vaultUtil;

    /**
     * @param VaultUtil $vaultUtil
     */
    public function __construct(
        VaultUtil $vaultUtil
    ) {
        $this->vaultUtil = $vaultUtil;
    }

    /**
     * Check if recurring data needs to be added to the transaction
     *
     * @param array $additionalInformation
     * @return bool
     */
    public function shouldAddRecurringData(array $additionalInformation): bool
    {
        if (empty($additionalInformation)) {
            return false;
        }

        if ($this->vaultUtil->validateVaultTokenEnabler($additionalInformation)) {
            return true;
        }

        return isset($additionalInformation['customer_id']) || isset($additionalInformation['tokenize']);
    }
}
