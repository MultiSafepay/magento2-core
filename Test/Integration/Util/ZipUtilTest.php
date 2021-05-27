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

namespace MultiSafepay\Test\Integration\Util;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\SystemReportUtil;
use MultiSafepay\ConnectCore\Util\ZipUtil;

class ZipUtilTest extends AbstractTestCase
{
    /**
     * @var ZipUtil
     */
    private $zipUtil;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var File
     */
    private $fileDriver;

    /**
     * @throws FileSystemException
     */
    protected function setUp(): void
    {
        $this->zipUtil = $this->getObjectManager()->create(ZipUtil::class);
        $this->filesystem = $this->getObjectManager()->create(Filesystem::class);
        $this->fileDriver = $this->getObjectManager()->create(File::class);

        $logDirectoryPath = $this->filesystem->getDirectoryRead(DirectoryList::LOG)->getAbsolutePath();
        $tmpDirectoryPath = $this->filesystem->getDirectoryRead(DirectoryList::TMP)->getAbsolutePath();
        $multisafepayLogPath = $logDirectoryPath . 'multisafepay.log';

        if (!$this->fileDriver->isDirectory($logDirectoryPath)) {
            $this->fileDriver->createDirectory($logDirectoryPath);
        }

        if (!$this->fileDriver->isDirectory($tmpDirectoryPath)) {
            $this->fileDriver->createDirectory($tmpDirectoryPath);
        }

        if (!$this->fileDriver->isExists($multisafepayLogPath)) {
            $resource = $this->fileDriver->fileOpen($multisafepayLogPath, 'wb');
            $this->fileDriver->fileWrite($resource, '');
            $this->fileDriver->fileClose($resource);
        }
    }

    /**
     * @throws FileSystemException
     */
    public function testZipLogFiles(): void
    {
        $response = $this->zipUtil->zipLogFiles();
        $contentDispositionHeader = $response->getHeader('content_disposition');

        self::assertEquals(
            'attachment; filename="multisafepay_logs.zip"',
            $contentDispositionHeader->getFieldValue()
        );
    }

    /**
     * @throws FileSystemException
     */
    protected function tearDown(): void
    {
        $multisafepayLogFile = $this->filesystem->getDirectoryRead(DirectoryList::LOG)->getAbsolutePath()
                               . 'multisafepay.log';
        $multisafepaySystemReportFile = $this->filesystem->getDirectoryRead(DirectoryList::TMP)
                                            ->getAbsolutePath() . SystemReportUtil::SYSTEM_REPORT_FILE_NAME;

        foreach ([$multisafepayLogFile, $multisafepaySystemReportFile] as $file) {
            $this->fileDriver->deleteFile($file);
        }
    }
}
