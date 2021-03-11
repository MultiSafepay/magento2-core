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

use InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Asset\File\NotFoundException;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use MultiSafepay\ConnectCore\Api\PaymentTokenInterface;
use MultiSafepay\ConnectCore\Logger\Logger;

class VaultUtil
{
    /**
     * @var AssetRepository
     */
    private $assetRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * VaultUtil constructor.
     *
     * @param AssetRepository $assetRepository
     * @param Logger $logger
     */
    public function __construct(
        AssetRepository $assetRepository,
        Logger $logger
    ) {
        $this->assetRepository = $assetRepository;
        $this->logger = $logger;
    }

    /**
     * @param array $additionalInformation
     * @return bool
     */
    public function validateVaultTokenEnabler(array $additionalInformation): bool
    {
        if (isset($additionalInformation[VaultConfigProvider::IS_ACTIVE_CODE])) {
            return (bool)$additionalInformation[VaultConfigProvider::IS_ACTIVE_CODE];
        }

        return false;
    }

    /**
     * @param string $type
     * @return array
     */
    public function getIcon(string $type): array
    {
        $path = $this->getImagePathByType($type);

        try {
            $asset = $this->assetRepository->createAsset($path);

            [$width, $height] = getimagesize($asset->getSourceFile());

            return [
                PaymentTokenInterface::ICON_URL => (string)$asset->getUrl(),
                PaymentTokenInterface::ICON_WIDTH => (int)$width,
                PaymentTokenInterface::ICON_HEIGHT => (int)$height,
            ];
        } catch (LocalizedException $localizedException) {
            $this->logger->logMissingVaultIcon($path, $localizedException);
        } catch (NotFoundException $notFoundException) {
            $this->logger->logMissingVaultIcon($path, $notFoundException);
        } catch (InvalidArgumentException $invalidArgumentException) {
            $this->logger->logMissingVaultIcon($path, $invalidArgumentException);
        }

        return [
            PaymentTokenInterface::ICON_URL => '',
            PaymentTokenInterface::ICON_WIDTH => 0,
            PaymentTokenInterface::ICON_HEIGHT => 0,
        ];
    }

    /**
     * @param string $type
     * @return string
     */
    private function getImagePathByType(string $type): string
    {
        return 'MultiSafepay_ConnectCore::images/multisafepay_' . strtolower($type) . '.png';
    }

    /**
     * @param string $methodCode
     * @return string
     */
    public function getActiveConfigPath(string $methodCode): string
    {
        return 'payment/' . $methodCode . '_vault/active';
    }
}
