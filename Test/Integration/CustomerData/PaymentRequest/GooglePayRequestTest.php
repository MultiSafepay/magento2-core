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
use MultiSafepay\ConnectCore\CustomerData\PaymentRequest\GooglePayRequest;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class GooglePayRequestTest extends AbstractTestCase
{
    /**
     * @var GooglePayRequest
     */
    private $googlePayRequest;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->googlePayRequest = $this->getObjectManager()->create(GooglePayRequest::class);
    }

    /**
     * Test if the Google Pay request contains all the expected data
     *
     * @magentoDataFixture   Magento/Sales/_files/quote.php
     * @magentoConfigFixture default_store payment/multisafepay_googlepay/active 1
     * @magentoConfigFixture default_store payment/multisafepay_googlepay/direct_button 1
     * @magentoConfigFixture default_store payment/multisafepay_googlepay/direct_button_mode PRODUCTION
     *
     * @return void
     * @throws Exception
     */
    public function testGooglePayRequest()
    {
        $quote = $this->getQuote('test01');
        $result = $this->googlePayRequest->create($quote);

        self::assertIsArray($result);

        self::assertArrayHasKey('isActive', $result);
        self::assertTrue($result['isActive']);

        self::assertArrayHasKey('googlePayButtonId', $result);
        self::assertEquals('multisafepay-google-pay-button', $result['googlePayButtonId']);

        self::assertArrayHasKey('mode', $result);
        self::assertEquals('PRODUCTION', $result['mode']);

        self::assertArrayHasKey('accountId', $result);
        self::assertArrayHasKey('merchantInfo', $result);
        self::assertIsArray($result['merchantInfo']);
        self::assertArrayHasKey('merchantName', $result['merchantInfo']);
        self::assertArrayHasKey('merchantId', $result['merchantInfo']);
    }

    /**
     * Test if the Google Pay request contains all the expected data
     *
     * @magentoDataFixture   Magento/Sales/_files/quote.php
     * @magentoConfigFixture default_store payment/multisafepay_googlepay/active 0
     * @magentoConfigFixture default_store payment/multisafepay_googlepay/direct_button 0
     *
     * @return void
     * @throws Exception
     */
    public function testDisabledGooglePayRequest()
    {
        $quote = $this->getQuote('test01');
        $result = $this->googlePayRequest->create($quote);

        self::assertNull($result);
    }

    /**
     * Test if the Google Pay request contains all the expected data
     *
     * @magentoConfigFixture default_store payment/multisafepay_googlepay/active 0
     * @magentoConfigFixture default_store payment/multisafepay_googlepay/direct_button 0
     *
     * @return void
     * @throws Exception
     */
    public function testGooglePayRequestWithoutQuote()
    {
        $result = $this->googlePayRequest->create(null);

        self::assertNull($result);
    }
}
