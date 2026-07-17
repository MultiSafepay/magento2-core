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

namespace MultiSafepay\Test\Integration\Util;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\SystemReportUtil;
use MultiSafepay\ConnectCore\Util\ZipUtil;
use PHPUnit\Framework\MockObject\MockObject;

class ZipUtilTest extends AbstractTestCase
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var File
     */
    private $fileDriver;

    /**
     * @var string
     */
    private $logFilePath;

    /**
     * @var array
     */
    private $capturedCreateArguments = [];

    /**
     * @var ResponseInterface|MockObject
     */
    private $responseMock;

    /**
     * @throws FileSystemException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = $this->getObjectManager()->create(Filesystem::class);
        $this->fileDriver = $this->getObjectManager()->create(File::class);

        $logDirectoryPath = $this->filesystem->getDirectoryRead(DirectoryList::LOG)->getAbsolutePath();
        $tmpDirectoryPath = $this->filesystem->getDirectoryRead(DirectoryList::TMP)->getAbsolutePath();
        $this->logFilePath = $logDirectoryPath . 'multisafepay.log';

        $this->ensureDirectoryExists($logDirectoryPath);
        $this->ensureDirectoryExists($tmpDirectoryPath);
        $this->createLogFileWithContent($this->logFilePath);
    }

    /**
     * zipLogFiles() must return the response produced by the FileFactory.
     *
     * @throws FileSystemException
     */
    public function testZipLogFilesReturnsFileFactoryResponse(): void
    {
        $response = $this->createZipUtilWithMockedFileFactory()->zipLogFiles();

        self::assertSame(
            $this->responseMock,
            $response,
            'zipLogFiles() should return the response created by the FileFactory.'
        );
    }

    /**
     * zipLogFiles() must hand the correct file name and zip content type to the FileFactory.
     *
     * @throws FileSystemException
     */
    public function testZipLogFilesRequestsZipDownloadFromFileFactory(): void
    {
        $this->createZipUtilWithMockedFileFactory()->zipLogFiles();

        [$fileName, $content, $baseDir, $contentType] = $this->capturedCreateArguments;

        self::assertSame(ZipUtil::ZIP_ARCHIVE_NAME, $fileName);
        self::assertSame('application/zip', $contentType);
        self::assertSame(DirectoryList::TMP, $baseDir);

        self::assertIsArray($content);
        self::assertSame('filename', $content['type']);
        self::assertSame(ZipUtil::ZIP_ARCHIVE_NAME, $content['value']);
        self::assertTrue($content['rm']);
    }

    /**
     * The zip archive must be assembled on disk before it is handed to the FileFactory for streaming.
     *
     * @throws FileSystemException
     */
    public function testZipLogFilesBuildsValidArchiveBeforeStreaming(): void
    {
        $tmpDirectoryPath = $this->filesystem->getDirectoryRead(DirectoryList::TMP)->getAbsolutePath();
        $zipPath = $tmpDirectoryPath . ZipUtil::ZIP_ARCHIVE_NAME;

        $this->createZipUtilWithMockedFileFactory()->zipLogFiles();

        self::assertTrue(
            $this->fileDriver->isExists($zipPath),
            'The multisafepay_logs.zip archive should be assembled in the tmp directory.'
        );

        $zip = new \ZipArchive();

        self::assertTrue(
            $zip->open($zipPath) === true,
            'The generated file should be a readable zip archive.'
        );

        self::assertGreaterThan(
            0,
            $zip->numFiles,
            'The zip archive should contain at least one file.'
        );

        $zip->close();
    }

    /**
     * Builds a ZipUtil instance whose FileFactory is mocked so no streaming or file deletion occurs.
     *
     * @return ZipUtil
     */
    private function createZipUtilWithMockedFileFactory(): ZipUtil
    {
        $this->responseMock = $this->getObjectManager()->create(ResponseInterface::class);

        $fileFactoryMock = $this->getMockBuilder(FileFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $fileFactoryMock->method('create')
            ->willReturnCallback(
                function (...$arguments) {
                    $this->capturedCreateArguments = $arguments;
                    return $this->responseMock;
                }
            );

        return $this->getObjectManager()->create(
            ZipUtil::class,
            ['fileFactory' => $fileFactoryMock]
        );
    }

    /**
     * @param string $directoryPath
     * @throws FileSystemException
     */
    private function ensureDirectoryExists(string $directoryPath): void
    {
        if (!$this->fileDriver->isDirectory($directoryPath)) {
            $this->fileDriver->createDirectory($directoryPath);
        }
    }

    /**
     * @param string $filePath
     * @throws FileSystemException
     */
    private function createLogFileWithContent(string $filePath): void
    {
        $resource = $this->fileDriver->fileOpen($filePath, 'wb');
        $this->fileDriver->fileWrite($resource, 'MultiSafepay integration test log entry.');
        $this->fileDriver->fileClose($resource);
    }

    /**
     * @throws FileSystemException
     */
    protected function tearDown(): void
    {
        $tmpDirectoryPath = $this->filesystem->getDirectoryRead(DirectoryList::TMP)->getAbsolutePath();

        $filesToRemove = [
            $this->logFilePath,
            $tmpDirectoryPath . SystemReportUtil::SYSTEM_REPORT_FILE_NAME,
            $tmpDirectoryPath . ZipUtil::ZIP_ARCHIVE_NAME,
        ];

        foreach ($filesToRemove as $file) {
            if ($file !== null && $this->fileDriver->isExists($file)) {
                $this->fileDriver->deleteFile($file);
            }
        }

        parent::tearDown();
    }
}
