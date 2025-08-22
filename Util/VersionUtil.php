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
use Magento\Framework\HTTP\Adapter\CurlFactory;
use MultiSafepay\ConnectCore\Client\CurlAdapter;
use MultiSafepay\ConnectCore\Client\Response;

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
     * @var Response
     */
    private $response;

    /**
     * VersionUtil constructor.
     *
     * @param JsonHandler $jsonHandler
     * @param CurlFactory $curlFactory
     * @param Response $response
     */
    public function __construct(
        JsonHandler $jsonHandler,
        CurlFactory $curlFactory,
        Response $response
    ) {
        $this->jsonHandler = $jsonHandler;
        $this->curlFactory = $curlFactory;
        $this->response = $response;
    }

    /**
     * Get the current meta package version
     *
     * @return string
     */
    public function getPluginVersion(): string
    {
        return '3.13.1';
    }

    /**
     * Try to get the latest version through a GitHub API request
     *
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
                CurlAdapter::GET,
                self::MULTISAFEPAY_MAGENTO_GITHUB_REPO_LINK,
                CurlAdapter::HTTP_11,
                $headers
            );
            $curlData = $this->response->fromString($curl->read());
            $curl->close();

            $content = $curlData['body'] ?? '';

            if ($content) {
                $pluginData = $this->jsonHandler->readJSON($content);
                $latestVersionRelease = $pluginData['tag_name'] ?? null;

                if ($latestVersionRelease && version_compare($latestVersionRelease, $this->getPluginVersion(), '>')) {
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
