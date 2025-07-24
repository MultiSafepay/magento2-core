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

namespace MultiSafepay\ConnectCore\Model\Ui\Gateway;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use MultiSafepay\ConnectCore\Model\Ui\GenericConfigProvider;

class BancontactConfigProvider extends GenericConfigProvider
{
    public const CODE = 'multisafepay_bancontact';
    public const VAULT_CODE = 'multisafepay_bancontact_vault';

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     * @throws Exception
     * @throws LocalizedException
     */
    public function getConfig(): array
    {
        return [
            'payment' => [
                $this->getCode() => [
                    'image' => $this->getImage(),
                    'vaultCode' => self::VAULT_CODE,
                    'is_preselected' => $this->isPreselected(),
                    'instructions' => $this->getInstructions()
                ],
            ],
        ];
    }

    /**
     * Get the gateway image path
     *
     * @param string $gatewayCode
     * @return string
     */
    public function getImagePath(string $gatewayCode): string
    {
        $extension = '.png';
        $storeId = $this->getStoreIdFromCheckoutSession();

        if ($this->config->getIconType($storeId) === 'svg') {
            $extension = '.svg';
        }

        return 'MultiSafepay_ConnectCore::images/' . $gatewayCode . $extension;
    }
}
