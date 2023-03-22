<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

// phpcs:ignoreFile

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Client;

use Magento\Framework\HTTP\Adapter\Curl;

class CurlAdapter extends Curl
{
    public const POST = 'POST';
    public const PUT = 'PUT';
    public const GET = 'GET';
    public const DELETE = 'DELETE';
    public const PATCH = 'PATCH';

    public const HTTP_11 = '1.1';
    public const HTTP_10 = '1.0';

    /**
     * Extend the original adapter and add support for PATCH requests
     *
     * @param string $method
     * @param string $url
     * @param string $http_ver
     * @param array $headers
     * @param string $body
     * @return string Request as text
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function write($method, $url, $http_ver = '1.1', $headers = [], $body = ''): string
    {
        $this->_applyConfig();

        // set url to post to
        curl_setopt($this->_getResource(), CURLOPT_URL, $url);
        curl_setopt($this->_getResource(), CURLOPT_RETURNTRANSFER, true);
        if ($method === self::POST) {
            curl_setopt($this->_getResource(), CURLOPT_POST, true);
            curl_setopt($this->_getResource(), CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($this->_getResource(), CURLOPT_POSTFIELDS, $body);
        } elseif ($method === self::PUT) {
            curl_setopt($this->_getResource(), CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($this->_getResource(), CURLOPT_POSTFIELDS, $body);
        } elseif ($method === self::GET) {
            curl_setopt($this->_getResource(), CURLOPT_HTTPGET, true);
            curl_setopt($this->_getResource(), CURLOPT_CUSTOMREQUEST, 'GET');
        } elseif ($method === self::DELETE) {
            curl_setopt($this->_getResource(), CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($this->_getResource(), CURLOPT_POSTFIELDS, $body);
        } elseif ($method === self::PATCH) {
            curl_setopt($this->_getResource(), CURLOPT_CUSTOMREQUEST, self::PATCH);
            curl_setopt($this->_getResource(), CURLOPT_POSTFIELDS, $body);
        }

        if ($http_ver === self::HTTP_11) {
            curl_setopt($this->_getResource(), CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        } elseif ($http_ver === self::HTTP_10) {
            curl_setopt($this->_getResource(), CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        }

        if (is_array($headers)) {
            curl_setopt($this->_getResource(), CURLOPT_HTTPHEADER, $headers);
        }

        /**
         * @internal Curl options setter have to be re-factored
         */
        $header = $this->_config['header'] ?? true;
        curl_setopt($this->_getResource(), CURLOPT_HEADER, $header);

        return $body;
    }
}
