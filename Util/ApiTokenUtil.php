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
use Magento\Framework\App\CacheInterface;
use Magento\Quote\Api\Data\CartInterface;
use MultiSafepay\ConnectCore\Model\Ui\GenericConfigProvider;
use MultiSafepay\Exception\InvalidDataInitializationException;

class ApiTokenUtil
{
    public const MULTISAFEPAY_API_TOKEN_CACHE = 'multisafepay-api-token';

    /**
     * @var GenericConfigProvider
     */
    private $genericConfigProvider;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var JsonHandler
     */
    private $jsonHandler;

    /**
     * @param GenericConfigProvider $genericConfigProvider
     * @param CacheInterface $cache
     * @param JsonHandler $jsonHandler
     */
    public function __construct(
        GenericConfigProvider $genericConfigProvider,
        CacheInterface $cache,
        JsonHandler $jsonHandler
    ) {
        $this->genericConfigProvider = $genericConfigProvider;
        $this->cache = $cache;
        $this->jsonHandler = $jsonHandler;
    }

    /**
     * Tries to retrieve the API token from the cache.
     *
     * If the API token does not exist, it will do an API call to MultiSafepay to retrieve the API token
     * and save it in the cache with a lifetime of 540 seconds (9 minutes).
     *
     * If the cache expires, $cacheData will be false instead of a string and the process will repeat
     *
     * @param CartInterface $quote
     * @return array
     * @throws Exception
     */
    public function getApiTokenFromCache(CartInterface $quote): array
    {
        $storeId = $quote->getStoreId();
        $cacheData = $this->cache->load(self::MULTISAFEPAY_API_TOKEN_CACHE . '-' . $storeId);

        if ($cacheData) {
            return $this->jsonHandler->readJSON($cacheData);
        }

        $apiTokenData = [
            'apiToken' => $this->genericConfigProvider->getApiToken($storeId) ?? '',
            'lifeTime' => time()
        ];

        $this->cache->save(
            $this->jsonHandler->convertToJSON($apiTokenData),
            self::MULTISAFEPAY_API_TOKEN_CACHE . '-' . $storeId,
            [],
            540
        );

        return $apiTokenData;
    }
}
