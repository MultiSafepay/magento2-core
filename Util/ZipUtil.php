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

namespace MultiSafepay\ConnectCore\Util;

use Exception;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\Io\File as IoFile;
use MultiSafepay\ConnectCore\Logger\Logger;
use ZipArchive;

class ZipUtil
{
    public const ZIP_ARCHIVE_NAME = 'multisafepay_logs.zip';

    /**
     * @var IoFile
     */
    private $file;

    /**
     * @var File
     */
    private $driverFile;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var SystemReportUtil
     */
    private $systemReportUtil;

    /**
     * ZipUtil constructor.
     *
     * @param DirectoryList $directoryList
     * @param FileFactory $fileFactory
     * @param IoFile $file
     * @param File $driverFile
     * @param Logger $logger
     * @param SystemReportUtil $systemReportUtil
     */
    public function __construct(
        DirectoryList $directoryList,
        FileFactory $fileFactory,
        IoFile $file,
        File $driverFile,
        Logger $logger,
        SystemReportUtil $systemReportUtil
    ) {
        $this->fileFactory = $fileFactory;
        $this->logger = $logger;
        $this->directoryList = $directoryList;
        $this->driverFile = $driverFile;
        $this->file = $file;
        $this->systemReportUtil = $systemReportUtil;
    }

    /**
     * @throws FileSystemException
     * @throws Exception
     */
    public function zipLogFiles(): ResponseInterface
    {
        if (!class_exists(ZipArchive::class)) {
            $this->logger->error('\ZipArchive class not found, zip file could not be created.');
        }

        $directory = $this->directoryList->getPath(DirectoryList::LOG);
        $path = $this->directoryList->getPath(DirectoryList::TMP);

        $zipFile = new ZipArchive();
        $zipFile->open(
            $path . DIRECTORY_SEPARATOR . self::ZIP_ARCHIVE_NAME,
            ZipArchive::CREATE | ZipArchive::OVERWRITE
        );

        $files = $this->driverFile->readDirectory($directory);

        foreach ($files as $filePath) {
            if (strpos($filePath, 'multisafepay') !== false) {
                $zipFile->addFile($filePath, $this->file->getPathInfo($filePath)['basename']);
            }
        }

        try {
            $this->systemReportUtil->createSystemReport();
            $systemReportFilePath = $path . DIRECTORY_SEPARATOR . SystemReportUtil::SYSTEM_REPORT_FILE_NAME;
            $zipFile->addFile($systemReportFilePath, $this->file->getPathInfo($systemReportFilePath)['basename']);
        } catch (FileSystemException $fileSystemException) {
            $this->logger->logFileSystemException($fileSystemException);
        }

        $zipFile->close();

        try {
            $this->systemReportUtil->flushSystemReport();
        } catch (FileSystemException $fileSystemException) {
            $this->logger->logFileSystemException($fileSystemException);
        }

        return $this->fileFactory->create(
            self::ZIP_ARCHIVE_NAME,
            [
                'type' => 'filename',
                'value' => self::ZIP_ARCHIVE_NAME,
                'rm' => true,
            ],
            DirectoryList::TMP,
            'application/zip'
        );
    }
}
