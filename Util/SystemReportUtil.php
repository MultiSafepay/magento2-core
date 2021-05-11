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

namespace MultiSafepay\ConnectCore\Util;

use Magento\Framework\App\State;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;

class SystemReportUtil
{
    /**
     * @var State
     */
    private $appState;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var File
     */
    private $driverFile;

    public function __construct(
        DirectoryList $directoryList,
        File $driverFile,
        State $appState
    )
    {
        $this->directoryList = $directoryList;
        $this->driverFile = $driverFile;
        $this->appState = $appState;
    }

    /**
     * @throws FileSystemException
     */
    public function createSystemReport(): void
    {
        $file = $this->driverFile->fileOpen($this->directoryList->getPath(DirectoryList::TMP) . DIRECTORY_SEPARATOR .
                                            'multisafepay_system_report.json',
            'w+');

        $text = 'this is a test';

        $this->driverFile->fileWrite($file, $text);
        $this->driverFile->fileClose($file);
    }

    /**
     * @return string
     */
    private function getMagentoMode(): string
    {
        return $this->appState->getMode();
    }

    /**
     * @return string
     */
    private function getRootServerPath(): string
    {
        return $this->directoryList->getRoot();
    }
}
