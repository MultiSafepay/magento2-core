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

use Magento\Framework\Exception\LocalizedException;
use MultiSafepay\ConnectCore\Model\Ui\GenericConfigProvider;

class BankTransferConfigProvider extends GenericConfigProvider
{
    public const CODE = 'multisafepay_banktransfer';

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getImage(): string
    {
        $path = $this->getImagePath($this->getCode());
        $this->assetRepository->createAsset($path);

        return $this->assetRepository->getUrl($path);
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

        switch ($this->localeResolver->getLocale()) {
            case 'nl_NL':
                $locale = 'nl';
                break;
            case 'fr_FR':
                $locale = 'fr';
                break;
            case 'de_DE':
                $locale = 'de';
                break;
            case 'es_ES':
                $locale = 'es';
                break;
            default:
                $locale = 'en';
        }

        return 'MultiSafepay_ConnectCore::images/' . $gatewayCode . '-' . $locale . $extension;
    }
}
