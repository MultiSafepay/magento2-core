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

namespace MultiSafepay\ConnectCore\Gateway\Validator;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Config\Config;
use Magento\Quote\Model\Quote;

class AmountValidator
{

    /**
     * @param Quote $quote
     * @param Config $config
     * @param string $methodCode
     * @return bool
     *
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate(Quote $quote, Config $config, string $methodCode): bool
    {
        $storeId = $quote->getStoreId();

        if ((int)$config->getValue('allow_amount', $storeId) === 1) {
            $baseGrandTotal = $quote->getBaseGrandTotal();

            if (($minAmount = $config->getValue('min_amount', $storeId)) && $baseGrandTotal < $minAmount) {
                return true;
            }

            if (($maxAmount = $config->getValue('max_amount', $storeId)) && $baseGrandTotal > $maxAmount) {
                return true;
            }
        }

        return false;
    }
}
