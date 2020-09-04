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
 * Copyright © 2020 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Factory;

use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\Sdk;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class SdkFactory
{
    /**
     * @var ClientInterface
     */
    private $psrClient;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * Client constructor.
     *
     * @param ClientInterface $psrClient
     * @param RequestFactoryInterface $requestFactory
     * @param StreamFactoryInterface $streamFactory
     * @param Config $config
     */
    public function __construct(
        ClientInterface $psrClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        Config $config
    ) {
        $this->psrClient = $psrClient;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->config = $config;
    }

    /**
     * @return Sdk
     */
    public function get(): Sdk
    {
        return new Sdk(
            $this->config->getApiKey(),
            $this->config->getMode(),
            $this->psrClient,
            $this->requestFactory,
            $this->streamFactory
        );
    }
}
