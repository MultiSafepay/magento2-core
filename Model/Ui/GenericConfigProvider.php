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

namespace MultiSafepay\ConnectCore\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use MultiSafepay\ConnectCore\Config\Config;

class GenericConfigProvider implements ConfigProviderInterface
{
    public const CODE = '';
    public const MULTISAFEPAY_LIST_CONFIG_PATH = '';

    /**
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * AbstractConfigProvider constructor.
     *
     * @param AssetRepository $assetRepository
     * @param Config $config
     */
    public function __construct(
        AssetRepository $assetRepository,
        Config $config
    ) {
        $this->assetRepository = $assetRepository;
        $this->config = $config;
    }

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
                    'is_preselected' => $this->isPreselected()
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
        $path = 'MultiSafepay_ConnectCore::images/' . $this->getCode() . '.png';

        $this->assetRepository->createAsset($path);
        return $this->assetRepository->getUrl($path);
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return static::CODE;
    }

    /**
     * @return bool
     */
    public function isPreselected(): bool
    {
        return $this->getCode() === $this->config->getPreselectedMethod();
    }

    /**
     * @param null $storeId
     * @return array
     */
    public function getGenericList($storeId = null): array
    {
        return (array)$this->config->getValueByPath(static::MULTISAFEPAY_LIST_CONFIG_PATH, $storeId);
    }

    /**
     * @param string $paymentCode
     * @return bool
     */
    public function isMultisafepayGenericMethod(string $paymentCode): bool
    {
        return strpos($paymentCode, static::CODE . '_') !== false;
    }
}
