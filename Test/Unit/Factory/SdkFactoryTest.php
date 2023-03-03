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

namespace MultiSafepay\ConnectCore\Test\Unit\Factory;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\Exception\InvalidApiKeyException;
use MultiSafepay\Sdk;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Http\Adapter\Guzzle6\Client;

class SdkFactoryTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);
    }

    /**
     * @return void
     */
    public function testValidateShouldThrowExceptionIfApiKeyIsEmpty(): void
    {
        $this->expectException(InvalidApiKeyException::class);

        $config = $this->createMock(Config::class);
        $config->method('getApiKey')->willThrowException(new InvalidApiKeyException);

        $psrClient = new Client();

        $arguments = ['config' => $config, 'psrClient' => $psrClient];
        $sdkFactory = $this->objectManager->getObject(SdkFactory::class, $arguments);
        $sdkFactory->create();
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testValidateShouldReturnTrueIfApiKeyIsNotEmpty(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('getApiKey')->willReturn('__FAKE_KEY__');

        $psrClient = new Client();

        $arguments = ['config' => $config, 'psrClient' => $psrClient];
        $sdkFactory = $this->objectManager->getObject(SdkFactory::class, $arguments);
        $result = $sdkFactory->create();
        $this->assertInstanceOf(Sdk::class, $result);
    }
}
