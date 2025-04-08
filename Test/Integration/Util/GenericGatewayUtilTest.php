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

namespace MultiSafepay\Test\Integration\Util;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\GenericGatewayUtil;

class GenericGatewayUtilTest extends AbstractTestCase
{
    public const GENERIC_GATEWAY_TEST_CODE = 'multisafepay_genericgateway_1';

    /**
     * @return void
     * @throws FileSystemException
     * @throws NoSuchEntityException
     */
    public function testReturnsEmptyStringIfImageDoesNotExist()
    {
        $filesystem = $this->createMock(Filesystem::class);
        $directoryRead = $this->createMock(ReadInterface::class);
        $config = $this->getObjectManager()->create(Config::class);
        $storeManager = $this->getObjectManager()->create(StoreManager::class);

        $filesystem->method('getDirectoryRead')->willReturn($directoryRead);
        $directoryRead->method('isFile')->willReturn(false);

        $genericGatewayUtil = new GenericGatewayUtil($config, $filesystem, $storeManager);

        $this->assertEquals(
            '',
            $genericGatewayUtil->getGenericFullImagePath('multisafepay_genericgateway_1')
        );
    }

    /**
     * @return void
     * @throws FileSystemException
     * @throws NoSuchEntityException
     */
    public function testReturnsImageUrlIfImageExists()
    {
        $filesystem = $this->createMock(Filesystem::class);
        $directoryRead = $this->createMock(ReadInterface::class);
        $config = $this->createMock(Config::class);
        $storeManager = $this->createMock(StoreManager::class);
        $store = $this->createMock(Store::class);

        $store->method('getBaseUrl')->with(UrlInterface::URL_TYPE_MEDIA)->willReturn('https://example.com/media/');
        $storeManager->method('getStore')->willReturn($store);
        $filesystem->method('getDirectoryRead')->willReturn($directoryRead);
        $directoryRead->method('isFile')->willReturn(true);
        $config->method('getValueByPath')->willReturn('multisafepay_genericgateway_1_image_1.png');

        $genericGatewayUtil = new GenericGatewayUtil($config, $filesystem, $storeManager);

        $this->assertEquals(
            'https://example.com/media/multisafepay/genericgateway/multisafepay_genericgateway_1_image_1.png',
            $genericGatewayUtil->getGenericFullImagePath('multisafepay_genericgateway_1')
        );
    }
}
