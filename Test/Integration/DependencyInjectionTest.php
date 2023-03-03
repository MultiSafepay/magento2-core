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

namespace MultiSafepay\ConnectCore\Test\Integration;

use Magento\Payment\Gateway\Command\CommandPool;
use Magento\Payment\Gateway\Command\GatewayCommand;
use Magento\Payment\Gateway\Config\Config;
use Magento\Payment\Model\Method\Adapter;
use Magento\Payment\Model\Method\Logger;
use MultiSafepay\ConnectCore\Api\StockReducerInterface;
use MultiSafepay\ConnectCore\Model\StockReducer;

/**
 * Class DependencyInjectionTest to test various Dependency Injection configurations
 */
class DependencyInjectionTest extends AbstractTestCase
{
    /**
     * Test to see if DI is working properly
     */
    public function testVirtualTypes()
    {
        $this->assertDiInstanceEquals(Adapter::class, 'MultiSafepayFacade');
        $this->assertDiInstanceEquals(Config::class, 'MultiSafepayConfig');
        $this->assertDiInstanceEquals(Logger::class, 'MultiSafepayLogger');
        $this->assertDiInstanceEquals(CommandPool::class, 'MultiSafepayCommandPool');
        $this->assertDiInstanceEquals(GatewayCommand::class, 'MultiSafepayInitializeCommand');
        $this->assertDiInstanceEquals(GatewayCommand::class, 'MultiSafepayRefundCommand');
        $this->assertDiInstanceEquals(StockReducerInterface::class, StockReducerInterface::class);
    }

    /**
     * Test if the mocked client is returned by the object factory
     */
    public function testIfMockVersionOfClientIsInPlace()
    {
        //$client = ObjectFactory::getClient();
        //$this->assertInstanceOf(MockHttpClient::class, $client->getHttpClient());

        //ObjectFactory::init();
        //$client = $this->getObjectManager()->get(Client::class);
        //$this->assertInstanceOf(MockHttpClient::class, $client->getHttpClient());
    }
}
