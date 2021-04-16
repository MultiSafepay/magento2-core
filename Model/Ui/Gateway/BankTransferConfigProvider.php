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
        $path = $this->getImagePath();
        $this->assetRepository->createAsset($path);

        return $this->assetRepository->getUrl($path);
    }

    /**
     * @return string
     */
    public function getImagePath(): string
    {
        switch ($this->localeResolver->getLocale()) {
            case 'nl_NL':
                return 'MultiSafepay_ConnectCore::images/' . $this->getCode() . '-nl.png';

            case 'fr_FR':
                return 'MultiSafepay_ConnectCore::images/' . $this->getCode() . '-fr.png';

            case 'de_DE':
                return 'MultiSafepay_ConnectCore::images/' . $this->getCode() . '-de.png';

            case 'es_ES':
                return 'MultiSafepay_ConnectCore::images/' . $this->getCode() . '-es.png';

            default:
                return 'MultiSafepay_ConnectCore::images/' . $this->getCode() . '-en.png';
        }
    }
}
