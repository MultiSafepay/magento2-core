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

namespace MultiSafepay\ConnectCore\Factory;

use Exception;
use MultiSafepay\ConnectCore\Client\Client;
use MultiSafepay\Exception\InvalidApiKeyException;
use Nyholm\Psr7\Factory\Psr17Factory;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\Sdk;

class SdkFactory
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Psr17Factory
     */
    protected $psr17Factory;

    /**
     * Client constructor.
     *
     * @param Config $config
     * @param Client $client
     */
    public function __construct(
        Config $config,
        Client $client
    ) {
        $this->config = $config;
        $this->client = $client;
        $this->psr17Factory = new Psr17Factory();
    }

    /**
     * Create the Sdk object
     *
     * @param int|null $storeId
     * @return Sdk
     * @throws Exception
     */
    public function create(?int $storeId = null): Sdk
    {
        return $this->get($storeId);
    }

    /**
     * Create the Sdk based on mode and api key
     *
     * @param bool $isLive
     * @param string $apiKey
     * @return Sdk
     * @throws InvalidApiKeyException
     */
    public function createWithModeAndApiKey(bool $isLive, string $apiKey): Sdk
    {
        return new Sdk(
            $apiKey,
            $isLive,
            $this->client,
            $this->psr17Factory,
            $this->psr17Factory
        );
    }

    /**
     * Get an instance of the Sdk
     *
     * @param int|null $storeId
     * @return Sdk
     * @throws Exception
     */
    private function get(?int $storeId = null): Sdk
    {
        return new Sdk(
            $this->config->getApiKey($storeId),
            $this->config->isLiveMode($storeId),
            $this->client,
            $this->psr17Factory,
            $this->psr17Factory
        );
    }
}
