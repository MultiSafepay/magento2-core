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

namespace MultiSafepay\ConnectCore\Gateway\Validator;

use Magento\Payment\Gateway\Config\Config;
use Magento\Quote\Api\Data\CartInterface;

class AmountValidator
{

    /**
     * @param CartInterface $quote
     * @param $config
     * @return bool
     */
    public function validate(CartInterface $quote, Config $config): bool
    {
        $storeId = $quote->getStoreId();

        if ((int)$config->getValue('allow_amount', $storeId) === 1) {
            $baseGrandTotal = $quote->getBaseGrandTotal();

            $minAmount = $config->getValue('min_amount', $storeId);
            if (($minAmount !== null) && $baseGrandTotal < $minAmount) {
                return true;
            }

            $maxAmount = $config->getValue('max_amount', $storeId);
            if (($maxAmount !== null) && $baseGrandTotal > $maxAmount) {
                return true;
            }
        }
        return false;
    }
}
