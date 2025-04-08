<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * Copyright © MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Util;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use MultiSafepay\ConnectCore\Config\Config;

class GenericGatewayUtil
{
    public const GENERIC_CONFIG_PATHS = [
        'multisafepay_gateways',
        'multisafepay_giftcards'
    ];

    public const GENERIC_CONFIG_IMAGE_PATH = '%s/gateway_image';

    /**
     * The tail part of directory path for uploading the logo
     */
    public const UPLOAD_DIR = 'multisafepay/genericgateway';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Config $config,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager
    ) {
        $this->config = $config;
        $this->filesystem = $filesystem;
        $this->storeManager = $storeManager;
    }

    /**
     * @param string $gatewayCode
     * @return string
     * @throws NoSuchEntityException
     * @throws FileSystemException
     */
    public function getGenericFullImagePath(string $gatewayCode): string
    {
        $path = $this->getImagePath($gatewayCode);
        $imageExists = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->isFile($path);

        return $imageExists ? $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . $path : '';
    }

    /**
     * @param string $gatewayCode
     * @return string
     */
    private function getImagePath(string $gatewayCode): string
    {
        foreach (self::GENERIC_CONFIG_PATHS as $path) {
            $configFullPath = $path . DIRECTORY_SEPARATOR . self::GENERIC_CONFIG_IMAGE_PATH;

            if ($configImagePath = $this->config->getValueByPath(sprintf($configFullPath, $gatewayCode))) {
                return self::UPLOAD_DIR . DIRECTORY_SEPARATOR . $configImagePath;
            }
        }

        return '';
    }
}
