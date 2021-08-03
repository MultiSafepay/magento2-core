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

namespace MultiSafepay\ConnectCore\Factory;

use Http\Adapter\Guzzle6\Client;
use Http\Factory\Guzzle\RequestFactory;
use Http\Factory\Guzzle\StreamFactory;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\Sdk;

class SdkFactory
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Client
     */
    private $psrClient;

    /**
     * @var StreamFactory
     */
    private $streamFactory;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * Client constructor.
     *
     * @param Client $psrClient
     * @param RequestFactory $requestFactory
     * @param StreamFactory $streamFactory
     * @param Config $config
     */
    public function __construct(
        Client $psrClient,
        RequestFactory $requestFactory,
        StreamFactory $streamFactory,
        Config $config
    ) {
        $this->psrClient = $psrClient;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->config = $config;
    }

    /**
     * @param int|null $storeId
     * @return Sdk
     */
    public function create(?int $storeId = null): Sdk
    {
        return $this->get($storeId);
    }

    /**
     * @param int|null $storeId
     * @return Sdk
     */
    private function get(int $storeId = null): Sdk
    {
        return new Sdk(
            $this->config->getApiKey($storeId),
            $this->config->isLiveMode($storeId),
            $this->psrClient,
            $this->requestFactory,
            $this->streamFactory
        );
    }
}
