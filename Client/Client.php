<?php
/**
 * NOTICE OF LICENSE
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * Copyright © MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Client;

use Exception;
use MultiSafepay\Exception\ApiException;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Client implements ClientInterface
{
    /**
     * @var CurlAdapter
     */
    private $curlAdapter;

    /**
     * @var Response
     */
    private $response;

    /**
     * Client constructor.
     *
     * @param CurlAdapter $curlAdapter
     * @param Response $response
     */
    public function __construct(
        CurlAdapter $curlAdapter,
        Response    $response
    ) {
        $this->curlAdapter = $curlAdapter;
        $this->response = $response;
    }

    /**
     * Send a Curl request and return a Psr-7 Response
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws ApiException
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
            $curlResponse = $this->response->fromString($this->curlAdapter->read());
        } catch (Exception $exception) {
            throw (new ApiException('Unable to read Curl response', 500, $exception));
        }

        $this->curlAdapter->close();

        return $this->createPsr7Response($curlResponse);
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
     * @param array $curlResponse
     * @return Psr7Response
     */
    private function createPsr7Response(array $curlResponse): Psr7Response
    {
        $response = new Psr7Response(
            $curlResponse['status_code'],
            $curlResponse['headers'],
            $curlResponse['body'],
            $curlResponse['http_version']
        );
        $response->getBody()->rewind();

        return $response;
    }
}
