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

use Exception;
use Magento\Framework\HTTP\Adapter\CurlFactory;
use Zend_Http_Client;
use Zend_Http_Response;

class VersionUtil
{
    private const MULTISAFEPAY_MAGENTO_GITHUB_REPO_LINK
        = 'https://api.github.com/repos/multisafepay/magento2/releases/latest';

    /**
     * @var JsonHandler
     */
    private $jsonHandler;

    /**
     * @var CurlFactory
     */
    private $curlFactory;

    /**
     * VersionUtil constructor.
     *
     * @param JsonHandler $jsonHandler
     * @param CurlFactory $curlFactory
     */
    public function __construct(
        JsonHandler $jsonHandler,
        CurlFactory $curlFactory
    ) {
        $this->jsonHandler = $jsonHandler;
        $this->curlFactory = $curlFactory;
    }

    /**
     * @return string
     */
    public function getPluginVersion(): string
    {
        return '2.12.0';
    }

    /**
     * @return array
     */
    public function getNewVersionsDataIfExist(): array
    {
        try {
            $headers = [
                "Accept-language: en\r\n" .
                "Cookie: foo=bar\r\n" .
                "User-Agent: PHP\r\n",
            ];

            $curl = $this->curlFactory->create();
            $curl->write(
                Zend_Http_Client::GET,
                self::MULTISAFEPAY_MAGENTO_GITHUB_REPO_LINK,
                Zend_Http_Client::HTTP_1,
                $headers
            );
            $curlData = Zend_Http_Response::fromString($curl->read());
            $curl->close();

            if ($content = $curlData->getBody()) {
                $pluginData = $this->jsonHandler->readJSON($content);
                $latestVersionRelease = $pluginData['tag_name'] ?? null;

                if ($latestVersionRelease
                    && version_compare($latestVersionRelease, $this->getPluginVersion(), '>')
                ) {
                    return [
                        'version' => (string)$latestVersionRelease,
                        'changelog' => $pluginData['body'] ?? '',
                        'url' => $pluginData['html_url'] ?? '',
                    ];
                }
            }
        } catch (Exception $exception) {
            return [];
        }

        return [];
    }
}
