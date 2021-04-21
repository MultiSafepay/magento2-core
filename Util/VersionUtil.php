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

class VersionUtil
{
    private const MULTISAFEPAY_MAGENTO_GITHUB_REPO_LINK
        = 'https://api.github.com/repos/multisafepay/magento2/releases/latest';

    /**
     * @var JsonHandler
     */
    private $jsonHandler;

    /**
     * VersionUtil constructor.
     *
     * @param JsonHandler $jsonHandler
     */
    public function __construct(JsonHandler $jsonHandler)
    {
        $this->jsonHandler = $jsonHandler;
    }

    /**
     * @return string
     */
    public function getPluginVersion(): string
    {
        return '2.7.1';
    }

    /**
     * @return array
     */
    public function getNewVersionsDataIfExist(): array
    {
        $options = [
            'http' => [
                'method' => "GET",
                'header' => "Accept-language: en\r\n" .
                            "Cookie: foo=bar\r\n" .
                            "User-Agent: PHP\r\n",
            ],
        ];

        $content = file_get_contents(
            self::MULTISAFEPAY_MAGENTO_GITHUB_REPO_LINK,
            false,
            stream_context_create($options)
        );

        if ($content) {
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

        return [];
    }
}
