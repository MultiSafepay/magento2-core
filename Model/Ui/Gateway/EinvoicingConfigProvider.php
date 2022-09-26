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
 * Copyright © 2022 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Model\Ui\Gateway;

use Magento\Framework\Exception\LocalizedException;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Model\Ui\GenericConfigProvider;

class EinvoicingConfigProvider extends GenericConfigProvider
{
    public const CODE = 'multisafepay_einvoicing';

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     * @throws LocalizedException
     */
    public function getConfig(): array
    {
        return [
            'payment' => [
                $this->getCode() => [
                    'image' => $this->getImage(),
                    'is_preselected' => $this->isPreselected(),
                    'transaction_type' => $this->getTransactionType(),
                    'checkout_fields' => $this->getCheckoutFields()
                ],
            ],
        ];
    }

    /**
     * Returns the selected checkout fields from the config
     *
     * @return array
     */
    private function getCheckoutFields(): array
    {
        $checkoutFields = $this->getPaymentConfig($this->getStoreIdFromCheckoutSession())[Config::CHECKOUT_FIELDS];

        if ($checkoutFieldsArray = explode(',', $checkoutFields)) {
            return $checkoutFieldsArray;
        }

        return [];
    }
}
