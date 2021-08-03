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

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Module\FullModuleList;
use Magento\Framework\Module\Manager;
use Magento\Payment\Model\Config;
use MultiSafepay\ConnectCore\Config\Config as MultiSafepayConfig;

class SystemReportUtil
{
    public const SYSTEM_REPORT_FILE_NAME = 'multisafepay_system_report.json';

    /**
     * @var VersionUtil
     */
    private $versionUtil;

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

    /**
     * @var ProductMetadataInterface
     */
    private $productMetaData;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var JsonHandler
     */
    private $jsonHandler;

    /**
     * @var Config
     */
    private $paymentConfig;

    /**
     * @var FullModuleList
     */
    private $moduleList;

    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * SystemReportUtil constructor.
     *
     * @param DirectoryList $directoryList
     * @param File $driverFile
     * @param FullModuleList $moduleList
     * @param JsonHandler $jsonHandler
     * @param ProductMetadataInterface $productMetadata
     * @param State $appState
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $paymentConfig
     * @param Manager $moduleManager
     * @param VersionUtil $versionUtil
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        DirectoryList $directoryList,
        File $driverFile,
        FullModuleList $moduleList,
        JsonHandler $jsonHandler,
        ProductMetadataInterface $productMetadata,
        State $appState,
        ScopeConfigInterface $scopeConfig,
        Config $paymentConfig,
        Manager $moduleManager,
        VersionUtil $versionUtil
    ) {
        $this->directoryList = $directoryList;
        $this->driverFile = $driverFile;
        $this->jsonHandler = $jsonHandler;
        $this->moduleList = $moduleList;
        $this->appState = $appState;
        $this->productMetaData = $productMetadata;
        $this->scopeConfig = $scopeConfig;
        $this->paymentConfig = $paymentConfig;
        $this->moduleManager = $moduleManager;
        $this->versionUtil = $versionUtil;
    }

    /**
     * @throws FileSystemException
     */
    public function createSystemReport(): void
    {
        $file = $this->openSystemReport();

        $systemReport = [
            'magento_info' => [
                'magento_version' => $this->getMagentoVersion(),
                'magento_mode' => $this->getMagentoMode(),
            ],
            'plugin_info' => [
                'plugin_version' => $this->versionUtil->getPluginVersion()
            ],
            'server_info' => [
                'root_path' => $this->getRootServerPath(),
                'magento_server_user' => $this->getServerUser(),
                'php_version' => $this->getPhpVersion(),
                'operating_system' => $this->getSystemInfo(),
                'web_server' => $this->getWebServerInfo(),
            ],
            'configuration_info' => [
                'tax_calculation' => $this->getTaxConfig(),
                'multisafepay_configuration' => [
                    'multisafepay_general_config' => $this->getMultiSafepayGeneralConfig(),
                    'multisafepay_advanced_config' => $this->getMultiSafepayAdvancedConfig(),
                ],
            ],
            'active_payment_methods' => $this->getActivePaymentMethods(),
            'third_party_modules' => $this->getThirdPartyModules(),
        ];

        $this->driverFile->fileWrite($file, $this->jsonHandler->convertToPrettyJSON($systemReport));
        $this->driverFile->fileClose($file);
    }

    /**
     * @throws FileSystemException
     */
    public function flushSystemReport(): void
    {
        $file = $this->openSystemReport();

        $this->driverFile->fileFlush($file);
    }

    /**
     * @return resource
     * @throws FileSystemException
     */
    public function openSystemReport()
    {
        return $this->driverFile->fileOpen(
            $this->directoryList->getPath(DirectoryList::TMP)
            . DIRECTORY_SEPARATOR . self::SYSTEM_REPORT_FILE_NAME,
            'w+'
        );
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

    /**
     * @return string
     */
    private function getMagentoVersion(): string
    {
        return $this->productMetaData->getName() . ' ' . $this->productMetaData->getEdition() . ' ' .
               $this->productMetaData->getVersion();
    }

    /**
     * @return array
     */
    private function getTaxConfig(): array
    {
        return (array)$this->scopeConfig->getValue('tax/calculation');
    }

    /**
     * @return array
     */
    private function getMultiSafepayGeneralConfig(): array
    {
        $config = (array)$this->scopeConfig->getValue('multisafepay/general');
        unset($config[MultiSafepayConfig::LIVE_API_KEY]);

        return $config;
    }

    /**
     * @return array
     */
    private function getMultiSafepayAdvancedConfig(): array
    {
        return (array)$this->scopeConfig->getValue('multisafepay/advanced');
    }

    /**
     * @return array
     */
    private function getActivePaymentMethods(): array
    {
        return array_keys($this->paymentConfig->getActiveMethods());
    }

    /**
     * @return array
     */
    private function getThirdPartyModules(): array
    {
        $allModules = $this->moduleList->getAll();
        $enabledThirdPartyModules = [];
        $disabledThirdPartyModules = [];

        foreach ($allModules as $module) {
            $moduleName = $module['name'];

            if (strpos($moduleName, 'Magento_') !== false) {
                continue;
            }

            if ($this->moduleManager->isEnabled($moduleName)) {
                $enabledThirdPartyModules[] = $moduleName;
                continue;
            }

            $disabledThirdPartyModules[] = $moduleName;
        }

        return ['enabled_modules' => $enabledThirdPartyModules, 'disabled_modules' => $disabledThirdPartyModules];
    }

    /**
     * @return string
     */
    private function getServerUser(): string
    {
        return function_exists('get_current_user') ? get_current_user() : 'unknown';
    }

    /**
     * @return string
     */
    private function getPhpVersion(): string
    {
        return PHP_VERSION;
    }

    /**
     * @return array
     */
    private function getSystemInfo(): array
    {
        return function_exists('php_uname') ? [
            'name' => PHP_OS,
            'host_name' => php_uname('n'),
            'release_name' => php_uname('r'),
            'version_info' => php_uname('v'),
            'machine_type' => php_uname('m'),
        ] : [];
    }

    /**
     * @return string
     */
    private function getWebServerInfo(): string
    {
        // phpcs:ignore Magento2.Security.Superglobal.SuperglobalUsageWarning
        return $_SERVER['SERVER_SOFTWARE'] ?? '';
    }
}
