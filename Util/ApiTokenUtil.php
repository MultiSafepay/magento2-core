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

use Magento\Framework\App\CacheInterface;
use Magento\Quote\Api\Data\CartInterface;
use MultiSafepay\ConnectCore\Model\Ui\GenericConfigProvider;

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
     * @param GenericConfigProvider $genericConfigProvider
     * @param CacheInterface $cache
     */
    public function __construct(
        GenericConfigProvider $genericConfigProvider,
        CacheInterface $cache
    ) {
        $this->genericConfigProvider = $genericConfigProvider;
        $this->cache = $cache;
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
     * @return string
     */
    public function getApiTokenFromCache(CartInterface $quote): string
    {
        $storeId = $quote->getStoreId();
        $cacheData = $this->cache->load(self::MULTISAFEPAY_API_TOKEN_CACHE . '-' . $storeId);

        if ($cacheData) {
            return $cacheData;
        }

        $apiToken = $this->genericConfigProvider->getApiToken($storeId) ?? '';

        $this->cache->save($apiToken, self::MULTISAFEPAY_API_TOKEN_CACHE . '-' . $storeId, [], 540);

        return $apiToken;
    }
}
