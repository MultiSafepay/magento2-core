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

namespace MultiSafepay\ConnectCore\Test\Integration\Method;

use Magento\Framework\App\Config\Initial;
use Magento\Framework\Event\ManagerInterface;
use Magento\Payment\Block\ConfigurableInfo;
use Magento\Payment\Block\Form;
use Magento\Payment\Gateway\Command\CommandPool;
use Magento\Payment\Gateway\Config\Config;
use Magento\Payment\Gateway\Config\ConfigValueHandler;
use Magento\Payment\Gateway\Config\ValueHandlerPool;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\CountryValidatorFactory;
use MultiSafepay\ConnectCore\Gateway\Validator\CurrencyValidatorFactory;
use MultiSafepay\ConnectCore\Model\Method\GenericAdapter;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\GenericGatewayConfigProvider;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use ReflectionException;
use ReflectionObject;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GenericAdapterTest extends AbstractTestCase
{
    public const GENERIC_GATEWAY_TEST_CODE = 'multisafepay_genericgateway_1';

    /**
     * @var GenericAdapter
     */
    private $genericAdapter;

    /**
     * @var ReflectionObject
     */
    private $genericAdapterReflector;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->genericAdapter = $this->getMockBuilder(GenericAdapter::class)->setConstructorArgs([
            $this->getObjectManager()->get(ManagerInterface::class),
            $this->getObjectManager()->create(
                ValueHandlerPool::class,
                [
                    'handlers' => [
                            'default' => ConfigValueHandler::class
                        ]
                ]
            ),
            $this->getObjectManager()->get(PaymentDataObjectFactory::class),
            $this->getObjectManager()->get(Config::class),
            $this->getObjectManager()->get(CountryValidatorFactory::class),
            $this->getObjectManager()->get(CurrencyValidatorFactory::class),
            $this->getObjectManager()->get(Initial::class),
            GenericGatewayConfigProvider::CODE,
            Form::class,
            ConfigurableInfo::class,
            $this->getObjectManager()->get(CommandPool::class)
        ])->setMethodsExcept(
            [
                'initGeneric',
                'getBaseGenericConfig',
                'setCode',
                'setBaseMethodCode',
                'getBaseCode',
                'getCode',
                'getConfiguredValue',
                'canUseForCountry',
                'validateByInstance',
                'setPaymentConfigCode',
                'canUseForCurrency'
            ]
        )->getMock();

        $this->genericAdapter->initGeneric(
            self::GENERIC_GATEWAY_TEST_CODE,
            GenericGatewayConfigProvider::CODE
        );

        $this->genericAdapterReflector = new ReflectionObject($this->genericAdapter);
    }

    /**
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     */
    public function testBaseGenericConfig(): void
    {
        $baseConfig = $this->genericAdapter->getBaseGenericConfig();

        self::assertEquals('MultiSafepayGenericGatewayFacade', $baseConfig['model']);
        self::assertEquals(self::GENERIC_GATEWAY_TEST_CODE, $this->genericAdapter->getCode());
    }

    /**
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @throws ReflectionException
     */
    public function testGetConfiguredValue(): void
    {
        $getConfiguredValueMetod = $this->genericAdapterReflector->getMethod('getConfiguredValue');
        $getConfiguredValueMetod->setAccessible(true);

        self::assertEquals('MultiSafepayGenericGatewayFacade', $getConfiguredValueMetod->invoke(
            $this->genericAdapter,
            'model',
        ));
    }

    /**
     * @magentoConfigFixture default_store payment/multisafepay_genericgateway_1/allowspecific 1
     * @magentoConfigFixture default_store payment/multisafepay_genericgateway_1/specificcountry US,NL
     */
    public function testCanUseForCountry(): void
    {
        self::assertTrue($this->genericAdapter->canUseForCountry('NL'));
    }
}
