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
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Model\Ui\GenericConfigProvider;

class CreditCardConfigProvider extends GenericConfigProvider
{
    public const CODE = 'multisafepay_creditcard';
    public const VAULT_CODE = 'multisafepay_creditcard_vault';

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
                    'vaultCode' => self::VAULT_CODE,
                    'is_preselected' => $this->isPreselected(),
                    'payment_type' => $this->getPaymentType(),
                    'instructions' => $this->getInstructions()
                ]
            ]
        ];
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getImage(): string
    {
        $extension = '.png';

        if ($this->config->getIconType() === 'svg') {
            $extension = '.svg';
        }

        $paymentConfig = $this->getPaymentConfig($this->getStoreIdFromCheckoutSession());

        // Return the default image if nothing can be found in the config
        if (!isset($paymentConfig[Config::PAYMENT_ICON]) || !$paymentConfig[Config::PAYMENT_ICON]) {
            $path = self::IMAGE_PATH . $this->getCode() . '_default' . $extension;
            $this->assetRepository->createAsset($path);

            return $this->assetRepository->getUrl($path);
        }

        $path = self::IMAGE_PATH . $this->getCode() . '_' . $paymentConfig[Config::PAYMENT_ICON] . $extension;
        $this->assetRepository->createAsset($path);

        return $this->assetRepository->getUrl($path);
    }
}
