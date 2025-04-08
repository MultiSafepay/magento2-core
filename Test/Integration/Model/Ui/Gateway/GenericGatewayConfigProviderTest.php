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

// phpcs:disable Generic.Files.LineLength.TooLong

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Test\Integration\Model\Ui\Gateway;

use Magento\Framework\Exception\NoSuchEntityException;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\CreditCardConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\GenericGatewayConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\VisaConfigProvider;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class GenericGatewayConfigProviderTest extends AbstractTestCase
{
    public const GENERIC_GATEWAY_TEST_CODE = 'multisafepay_genericgateway_1';

    /**
     * @var GenericGatewayConfigProvider
     */
    private $genericGatewayConfigProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->genericGatewayConfigProvider = $this->getObjectManager()->create(GenericGatewayConfigProvider::class);
    }

    /**
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @dataProvider         gatewaysDataProvider
     */
    public function testIsMultisafepayGenericMethodVariations(string $paymentCode, bool $expected): void
    {
        self::assertEquals($expected, $this->genericGatewayConfigProvider->isMultisafepayGenericMethod($paymentCode));
    }

    /**
     * @return array[]
     */
    public function gatewaysDataProvider(): array
    {
        return [
            [
                VisaConfigProvider::CODE,
                false,
            ],
            [
                CreditCardConfigProvider::CODE,
                false,
            ],
            [
                self::GENERIC_GATEWAY_TEST_CODE,
                true,
            ],
            [
                GenericGatewayConfigProvider::CODE,
                false,
            ],
        ];
    }
}
