<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

// phpcs:ignoreFile

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Client;

use Magento\Framework\HTTP\Adapter\Curl;
use Laminas\Http\Request as LaminasRequest;

class CurlAdapter extends Curl
{
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
        if ($method === LaminasRequest::METHOD_POST) {
            curl_setopt($this->_getResource(), CURLOPT_POST, true);
            curl_setopt($this->_getResource(), CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($this->_getResource(), CURLOPT_POSTFIELDS, $body);
        } elseif ($method === LaminasRequest::METHOD_PUT) {
            curl_setopt($this->_getResource(), CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($this->_getResource(), CURLOPT_POSTFIELDS, $body);
        } elseif ($method === LaminasRequest::METHOD_GET) {
            curl_setopt($this->_getResource(), CURLOPT_HTTPGET, true);
            curl_setopt($this->_getResource(), CURLOPT_CUSTOMREQUEST, 'GET');
        } elseif ($method === LaminasRequest::METHOD_DELETE) {
            curl_setopt($this->_getResource(), CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($this->_getResource(), CURLOPT_POSTFIELDS, $body);
        } elseif ($method === LaminasRequest::METHOD_PATCH) {
            curl_setopt($this->_getResource(), CURLOPT_CUSTOMREQUEST, LaminasRequest::METHOD_PATCH);
            curl_setopt($this->_getResource(), CURLOPT_POSTFIELDS, $body);
        }

        if ($http_ver === CURL_HTTP_VERSION_1_1) {
            curl_setopt($this->_getResource(), CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        } elseif ($http_ver === CURL_HTTP_VERSION_1_0) {
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
