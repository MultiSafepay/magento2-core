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

/**
 * @deprecated No longer supported by MultiSafepay
 */
class SantanderConfigProvider extends GenericConfigProvider
{
    public const CODE = 'multisafepay_santander';

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
}
