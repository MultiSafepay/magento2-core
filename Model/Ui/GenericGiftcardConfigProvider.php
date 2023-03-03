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

namespace MultiSafepay\ConnectCore\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Asset\Repository as AssetRepository;

/**
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class GenericGiftcardConfigProvider extends GenericConfigProvider
{
    /**
     * @return string
     * @throws LocalizedException
     */
    public function getImage(): string
    {
        $path = 'MultiSafepay_ConnectCore::images/giftcard/' . $this->getCode() . '.png';

        $this->assetRepository->createAsset($path);
        return $this->assetRepository->getUrl($path);
    }
}
