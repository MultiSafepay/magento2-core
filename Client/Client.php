<?php
/**
 * NOTICE OF LICENSE
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * Copyright © 2022 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Client;

use MultiSafepay\Exception\ApiException;
use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend_Http_Exception;
use Zend_Http_Response;

class Client implements ClientInterface
{
    /**
     * @var CurlAdapter
     */
    private $curlAdapter;

    /**
     * Client constructor.
     *
     * @param CurlAdapter $curlAdapter
     */
    public function __construct(
        CurlAdapter $curlAdapter
    ) {
        $this->curlAdapter = $curlAdapter;
    }

    /**
     * Send a Curl request and return a Psr-7 Response
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $request->getBody()->rewind();

        $this->curlAdapter->write(
            $request->getMethod(),
            $request->getUri(),
            $request->getProtocolVersion(),
            $this->prepareCurlHeaders($request->getHeaders()),
            $request->getBody()->getContents()
        );

        try {
            $curlResponse = Zend_Http_Response::fromString($this->curlAdapter->read());
        } catch (Zend_Http_Exception $zendHttpException) {
            throw (new ApiException('Unable to read Curl response', 500, $zendHttpException));
        }

        $this->curlAdapter->close();

        try {
            return $this->createPsr7Response($curlResponse);
        } catch (Zend_Http_Exception $zendHttpException) {
            throw (new ApiException('Unable to create Psr-7 response', 500, $zendHttpException));
        }
    }

    /**
     * Prepare the headers into a format that is expected by the Curl Client
     *
     * @param array $headers
     * @return array
     */
    private function prepareCurlHeaders(array $headers): array
    {
        foreach ($headers as $name => $value) {
            $curlHeaders[] = $name . ": " . implode(", ", $value);
        }

        return $curlHeaders ?? [];
    }

    /**
     * Create a Psr-7 response based on the response received from Curl
     *
     * @param Zend_Http_Response $curlResponse
     * @return Response
     * @throws Zend_Http_Exception
     */
    private function createPsr7Response(Zend_Http_Response $curlResponse): Response
    {
        $response = new Response(
            $curlResponse->getStatus(),
            $curlResponse->getHeaders(),
            $curlResponse->getBody(),
            $curlResponse->getVersion()
        );
        $response->getBody()->rewind();

        return $response;
    }
}
