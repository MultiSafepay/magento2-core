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

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DriverInterface as FileDriverInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Serialize\SerializerInterface;

class VersionUtil
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var FileDriverInterface
     */
    private $fileDriver;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * PluginDetails constructor.
     *
     * @param DirectoryList $directoryList
     * @param FileDriverInterface $fileDriver
     * @param SerializerInterface $serializer
     * @param ModuleListInterface $moduleList
     */
    public function __construct(
        DirectoryList $directoryList,
        FileDriverInterface $fileDriver,
        SerializerInterface $serializer,
        ModuleListInterface $moduleList
    ) {
        $this->directoryList = $directoryList;
        $this->fileDriver = $fileDriver;
        $this->serializer = $serializer;
        $this->moduleList = $moduleList;
    }

    /**
     * @return string
     */
    public function getPluginVersion(): string
    {
        // @todo: Find a better way to find the composer.json file
        $composerFile = $this->directoryList->getRoot() . '/vendor/multisafepay/magento2/composer.json';
        if ($this->fileDriver->isExists($composerFile)) {
            return $this->getVersionFromComposerFile($composerFile);
        }

        return 'unknown';
    }

    /**
     * @param string $composerFile
     * @return string
     * @throws FileSystemException
     */
    public function getVersionFromComposerFile(string $composerFile): string
    {
        $composerData = $this->serializer->unserialize($this->fileDriver->fileGetContents($composerFile));
        return (isset($composerData['version'])) ? $composerData['version'] : 'unknown';
    }

    /**
     * @return string[]
     */
    public function getModuleNames(): array
    {
        $moduleNames = [];
        foreach ($this->moduleList->getAll() as $module) {
            if (!preg_match('/^MultiSafepay_/', $module['name'])) {
                continue;
            }

            $moduleNames[] = $module['name'];
        }

        return $moduleNames;
    }
}
