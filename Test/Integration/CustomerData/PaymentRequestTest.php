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

namespace MultiSafepay\ConnectCore\Test\Integration\CustomerData;

use Exception;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\CreditCardConfigProvider as CreditCardConfig;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\CustomerData\PaymentRequest;
use MultiSafepay\ConnectCore\Test\Integration\CustomerData\PaymentRequest\PaymentComponentRequestTest;

class PaymentRequestTest extends AbstractTestCase
{
    /**
     * @var PaymentRequest
     */
    private $paymentRequest;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentRequest = $this->getObjectManager()->create(PaymentRequest::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/quote.php
     *
     * @return void
     * @throws Exception
     */
    public function testDefaultPaymentRequest()
    {
        $result = $this->paymentRequest->getSectionData();
        $this->assertDefaultValues($result);
    }

    /**
     * Test if the Google Pay data is in the payment request when Apple Pay Direct is activated
     *
     * @magentoDataFixture   Magento/Sales/_files/quote.php
     * @magentoConfigFixture default_store payment/multisafepay_applepay/active 1
     * @magentoConfigFixture default_store payment/multisafepay_applepay/direct_button 1
     *
     * @return void
     * @throws Exception
     */
    public function testApplePayPaymentRequest()
    {
        $result = $this->paymentRequest->getSectionData();
        $this->assertDefaultValues($result);
        $this->assertApplePayRequestValues($result);
    }

    /**
     * Test if the Google Pay data is in the payment request when Google Pay Direct is activated
     *
     * @magentoDataFixture   Magento/Sales/_files/quote.php
     * @magentoConfigFixture default_store payment/multisafepay_googlepay/active 1
     * @magentoConfigFixture default_store payment/multisafepay_googlepay/direct_button 1
     *
     * @return void
     * @throws Exception
     */
    public function testGooglePayPaymentRequest()
    {
        $result = $this->paymentRequest->getSectionData();
        $this->assertDefaultValues($result);
        $this->assertGooglePayRequestValues($result);
    }

    /**
     * Test if the payment component data is in the payment request when payment component is activated
     *
     * @magentoDbIsolation enabled
     * @magentoDataFixture   Magento/Sales/_files/quote.php
     * @magentoConfigFixture default_store payment/multisafepay_creditcard/active 1
     * @magentoConfigFixture default_store payment/multisafepay_creditcard/payment_type payment_component
     *
     * @return void
     * @throws Exception
     */
    public function testPaymentComponentRequest()
    {
        $result = $this->paymentRequest->getSectionData();
        $this->assertDefaultValues($result);
        $this->assertPaymentComponentValues($result);
    }

    /**
     * Test if the Google Pay data is in the payment request when Google Pay Direct is activated
     *
     * @magentoDbIsolation enabled
     * @magentoDataFixture   Magento/Sales/_files/quote.php
     * @magentoConfigFixture default_store payment/multisafepay_googlepay/active 1
     * @magentoConfigFixture default_store payment/multisafepay_googlepay/direct_button 1
     * @magentoConfigFixture default_store payment/multisafepay_applepay/active 1
     * @magentoConfigFixture default_store payment/multisafepay_applepay/direct_button 1
     * @magentoConfigFixture default_store payment/multisafepay_creditcard/active 1
     * @magentoConfigFixture default_store payment/multisafepay_creditcard/payment_type payment_component
     *
     * @return void
     * @throws Exception
     */
    public function testApplePayGooglePayAndPaymentComponentInSameRequest()
    {
        $result = $this->paymentRequest->getSectionData();

        $this->assertDefaultValues($result);
        $this->assertApplePayRequestValues($result);
        $this->assertGooglePayRequestValues($result);
        $this->assertPaymentComponentValues($result);
    }

    /**
     * Assert the default payment request values
     *
     * @param array $result
     * @return void
     */
    private function assertDefaultValues(array $result)
    {
        self::assertIsArray($result);
        self::assertArrayHasKey('environment', $result);
        self::assertEquals('test', $result['environment']);
        self::assertArrayHasKey('locale', $result);
        self::assertEquals('en_US', $result['locale']);
        self::assertArrayHasKey('cartTotal', $result);
        self::assertEquals(null, $result['cartTotal']);
        self::assertArrayHasKey('currency', $result);
        self::assertEquals('', $result['currency']);
        self::assertArrayHasKey('storeId', $result);
        self::assertEquals(1, $result['storeId']);
    }

    /**
     * Assert the Apple Pay request values
     *
     * @param array $result
     * @return void
     */
    private function assertApplePayRequestValues(array $result)
    {
        self::assertArrayHasKey('applePayButton', $result);
        self::assertIsArray($result['applePayButton']);

        self::assertArrayHasKey('isActive', $result['applePayButton']);
        self::assertTrue($result['applePayButton']['isActive']);

        self::assertArrayHasKey('applePayButtonId', $result['applePayButton']);
        self::assertEquals('multisafepay-apple-pay-button', $result['applePayButton']['applePayButtonId']);

        self::assertArrayHasKey('getMerchantSessionUrl', $result['applePayButton']);
        self::assertEquals('http://localhost/index.php/multisafepay/apple/session/', $result['applePayButton']['getMerchantSessionUrl']); // phpcs:ignore

        self::assertArrayHasKey('cartItems', $result['applePayButton']);
        self::assertIsArray($result['applePayButton']['cartItems']);
        self::assertEmpty($result['applePayButton']['cartItems']);

        self::assertArrayHasKey('additionalTotalItems', $result['applePayButton']);
        self::assertIsArray($result['applePayButton']['additionalTotalItems']);
        self::assertEmpty($result['applePayButton']['additionalTotalItems']);
    }

    /**
     * Assert the Google Pay request values
     *
     * @param array $result
     * @return void
     */
    private function assertGooglePayRequestValues(array $result)
    {
        self::assertArrayHasKey('googlePayButton', $result);
        self::assertIsArray($result['googlePayButton']);

        self::assertArrayHasKey('isActive', $result['googlePayButton']);
        self::assertTrue($result['googlePayButton']['isActive']);

        self::assertArrayHasKey('googlePayButtonId', $result['googlePayButton']);
        self::assertEquals('multisafepay-google-pay-button', $result['googlePayButton']['googlePayButtonId']);

        self::assertArrayHasKey('mode', $result['googlePayButton']);
        self::assertEquals('PRODUCTION', $result['googlePayButton']['mode']);

        self::assertArrayHasKey('accountId', $result['googlePayButton']);
        self::assertArrayHasKey('merchantInfo', $result['googlePayButton']);
        self::assertIsArray($result['googlePayButton']['merchantInfo']);
        self::assertArrayHasKey('merchantName', $result['googlePayButton']['merchantInfo']);
        self::assertArrayHasKey('merchantId', $result['googlePayButton']['merchantInfo']);
    }

    /**
     * Assert the payment component request values
     *
     * @param array $result
     * @return void
     */
    private function assertPaymentComponentValues(array $result)
    {
        self::assertArrayHasKey('paymentComponentContainerId', $result);
        self::assertEquals('multisafepay-payment-component', $result['paymentComponentContainerId']);

        self::assertArrayHasKey('paymentComponentConfig', $result);
        self::assertIsArray($result['paymentComponentConfig']);

        self::assertArrayHasKey(CreditCardConfig::CODE, $result['paymentComponentConfig']);

        self::assertArrayHasKey('paymentMethod', $result['paymentComponentConfig'][CreditCardConfig::CODE]);
        self::assertEquals(CreditCardConfig::CODE, $result['paymentComponentConfig'][CreditCardConfig::CODE]['paymentMethod']); // phpcs:ignore

        self::assertArrayHasKey('gatewayCode', $result['paymentComponentConfig'][CreditCardConfig::CODE]);
        self::assertEquals('CREDITCARD', $result['paymentComponentConfig'][CreditCardConfig::CODE]['gatewayCode']); // phpcs:ignore

        self::assertArrayHasKey('paymentType', $result['paymentComponentConfig'][CreditCardConfig::CODE]);
        self::assertEquals('payment_component', $result['paymentComponentConfig'][CreditCardConfig::CODE]['paymentType']); // phpcs:ignore

        self::assertArrayHasKey('additionalInfo', $result['paymentComponentConfig'][CreditCardConfig::CODE]);
        self::assertIsArray($result['paymentComponentConfig'][CreditCardConfig::CODE]['additionalInfo']);

        self::assertArrayHasKey('image', $result['paymentComponentConfig'][CreditCardConfig::CODE]['additionalInfo']);
        self::assertStringContainsString(
            PaymentComponentRequestTest::IMAGE_URL_START,
            $result['paymentComponentConfig'][CreditCardConfig::CODE]['additionalInfo']['image']
        );

        self::assertStringContainsString(
            PaymentComponentRequestTest::IMAGE_URL_END,
            $result['paymentComponentConfig'][CreditCardConfig::CODE]['additionalInfo']['image']
        );

        self::assertArrayHasKey('vaultCode', $result['paymentComponentConfig'][CreditCardConfig::CODE]['additionalInfo']); // phpcs:ignore
        self::assertEquals(CreditCardConfig::VAULT_CODE, $result['paymentComponentConfig'][CreditCardConfig::CODE]['additionalInfo']['vaultCode']); //phpcs:ignore

        self::assertArrayHasKey('is_preselected', $result['paymentComponentConfig'][CreditCardConfig::CODE]['additionalInfo']); // phpcs:ignore
        self::assertEquals(false, $result['paymentComponentConfig'][CreditCardConfig::CODE]['additionalInfo']['is_preselected']); // phpcs:ignore

        self::assertArrayHasKey('payment_type', $result['paymentComponentConfig'][CreditCardConfig::CODE]['additionalInfo']); // phpcs:ignore
        self::assertEquals('payment_component', $result['paymentComponentConfig'][CreditCardConfig::CODE]['additionalInfo']['payment_type']); // phpcs:ignore

        self::assertArrayHasKey('apiToken', $result);
        self::assertIsString($result['apiToken']);
    }
}
