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

namespace MultiSafepay\ConnectCore\Test\Integration\CustomerData\PaymentRequest;

use Exception;
use MultiSafepay\ConnectCore\CustomerData\PaymentRequest\PaymentComponentRequest;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\CreditCardConfigProvider as CreditCardConfig;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class PaymentComponentRequestTest extends AbstractTestCase
{
    public const IMAGE_URL_START = 'http://localhost/';
    public const IMAGE_URL_END = '/frontend/Magento/luma/en_US/MultiSafepay_ConnectCore/images/multisafepay_creditcard_default.png'; //phpcs:ignore

    /**
     * @var PaymentComponentRequest
     */
    private $paymentComponentRequest;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentComponentRequest = $this->getObjectManager()->create(PaymentComponentRequest::class);
    }

    /**
     * Test if the payment component request contains all the expected data
     *
     * @magentoDataFixture   Magento/Sales/_files/quote.php
     * @magentoConfigFixture default_store payment/multisafepay_creditcard/active 1
     * @magentoConfigFixture default_store payment/multisafepay_creditcard/payment_type payment_component
     *
     * @return void
     * @throws Exception
     */
    public function testEnabledPaymentComponentRequest()
    {
        $quote = $this->getQuote('test01');
        $result = $this->paymentComponentRequest->create($quote);

        self::assertIsArray($result);
        self::assertArrayHasKey(CreditCardConfig::CODE, $result);

        self::assertArrayHasKey('paymentMethod', $result[CreditCardConfig::CODE]);
        self::assertEquals(CreditCardConfig::CODE, $result[CreditCardConfig::CODE]['paymentMethod']);

        self::assertArrayHasKey('gatewayCode', $result[CreditCardConfig::CODE]);
        self::assertEquals('CREDITCARD', $result[CreditCardConfig::CODE]['gatewayCode']);

        self::assertArrayHasKey('paymentType', $result[CreditCardConfig::CODE]);
        self::assertEquals('payment_component', $result[CreditCardConfig::CODE]['paymentType']);

        self::assertArrayHasKey('additionalInfo', $result[CreditCardConfig::CODE]);
        self::assertIsArray($result[CreditCardConfig::CODE]['additionalInfo']);

        self::assertArrayHasKey('image', $result[CreditCardConfig::CODE]['additionalInfo']);
        self::assertStringContainsString(self::IMAGE_URL_START, $result[CreditCardConfig::CODE]['additionalInfo']['image']); //phpcs:ignore
        self::assertStringContainsString(self::IMAGE_URL_END, $result[CreditCardConfig::CODE]['additionalInfo']['image']); //phpcs:ignore

        self::assertArrayHasKey('vaultCode', $result[CreditCardConfig::CODE]['additionalInfo']);
        self::assertEquals(CreditCardConfig::VAULT_CODE, $result[CreditCardConfig::CODE]['additionalInfo']['vaultCode']); //phpcs:ignore

        self::assertArrayHasKey('is_preselected', $result[CreditCardConfig::CODE]['additionalInfo']);
        self::assertEquals(false, $result[CreditCardConfig::CODE]['additionalInfo']['is_preselected']);

        self::assertArrayHasKey('payment_type', $result[CreditCardConfig::CODE]['additionalInfo']);
        self::assertEquals('payment_component', $result[CreditCardConfig::CODE]['additionalInfo']['payment_type']);
    }

    /**
     * Test if the payment component request contains all the expected data
     *
     * @magentoDataFixture   Magento/Sales/_files/quote.php
     * @magentoConfigFixture default_store payment/multisafepay_creditcard/active 0
     * @magentoConfigFixture default_store payment/multisafepay_creditcard/payment_type payment_component
     *
     * @return void
     * @throws Exception
     */
    public function testDisabledPaymentComponentRequest()
    {
        $quote = $this->getQuote('test01');
        $result = $this->paymentComponentRequest->create($quote);

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * Test if the payment component request contains all the expected data
     *
     * @magentoConfigFixture default_store payment/multisafepay_creditcard/active 1
     * @magentoConfigFixture default_store payment/multisafepay_creditcard/payment_type payment_component
     *
     * @return void
     * @throws Exception
     */
    public function testPaymentComponentRequestWithoutQuote()
    {
        $result = $this->paymentComponentRequest->create(null);

        self::assertIsArray($result);
        self::assertEmpty($result);
    }
}
