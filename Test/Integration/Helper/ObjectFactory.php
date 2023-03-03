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

namespace MultiSafepay\ConnectCore\Test\Integration\Helper;

use Http\Mock\Client as MockHttpClient;
use Magento\TestFramework\Helper\Bootstrap;
use MultiSafepay\Client\Client;
use MultiSafepay\Sdk;
use MultiSafepay\Util\Version;

class ObjectFactory
{
    /**
     * Set various instances in the DI configuration of Magento
     */
    public static function init()
    {
        $objectManager = Bootstrap::getObjectManager();
        $objectManager->configure(
            [
                'preferences' => [
                    Client::class => self::getClient(),
                    Sdk::class => self::getSdk(),
                ],
            ]
        );
    }

    /**
     * @return Client
     */
    public static function getClient(string $apiKey = '__valid__'): Client
    {
        Version::getInstance()->addPluginVersion('integration-test');
        $mockClient = new MockHttpClient();
        return new Client($apiKey, false, $mockClient);
    }

    /**
     * @param string $apiKey
     * @return Sdk
     */
    public static function getSdk(string $apiKey = '__valid__'): Sdk
    {
        Version::getInstance()->addPluginVersion('integration-test');
        $mockHttpClient = new MockHttpClient();
        return new Sdk($apiKey, false, $mockHttpClient);
    }
}
