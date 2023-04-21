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
use MultiSafepay\ConnectCore\CustomerData\PaymentRequest\ApplePayRequest;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class ApplePayRequestTest extends AbstractTestCase
{
    /**
     * @var ApplePayRequest
     */
    private $applePayRequest;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->applePayRequest = $this->getObjectManager()->create(ApplePayRequest::class);
    }

    /**
     * Test if the Apple Pay request contains all the expected data
     *
     * @magentoDataFixture   Magento/Sales/_files/quote.php
     * @magentoConfigFixture default_store payment/multisafepay_applepay/active 1
     * @magentoConfigFixture default_store payment/multisafepay_applepay/direct_button 1
     *
     * @return void
     * @throws Exception
     */
    public function testEnabledApplePayRequest()
    {
        $quote = $this->getQuote('test01');
        $result = $this->applePayRequest->create($quote);

        self::assertIsArray($result);

        self::assertArrayHasKey('isActive', $result);
        self::assertTrue($result['isActive']);

        self::assertArrayHasKey('applePayButtonId', $result);
        self::assertEquals('multisafepay-apple-pay-button', $result['applePayButtonId']);

        self::assertArrayHasKey('getMerchantSessionUrl', $result);
        self::assertEquals('http://localhost/index.php/multisafepay/apple/session/', $result['getMerchantSessionUrl']);

        self::assertArrayHasKey('cartItems', $result);
        self::assertIsArray($result['cartItems']);
        self::assertArrayHasKey('simple', $result['cartItems']);
        self::assertIsArray($result['cartItems']['simple']);
        self::assertArrayHasKey('label', $result['cartItems']['simple']);
        self::assertEquals('Simple Product (SKU: simple); Qty: 1.00', $result['cartItems']['simple']['label']);
        self::assertArrayHasKey('price', $result['cartItems']['simple']);
        self::assertEquals(10, $result['cartItems']['simple']['price']);

        self::assertArrayHasKey('additionalTotalItems', $result);
        self::assertIsArray($result['additionalTotalItems']);
        self::assertEmpty($result['additionalTotalItems']);
    }

    /**
     * Test if the Apple Pay request contains all the expected data
     *
     * @magentoDataFixture   Magento/Sales/_files/quote.php
     * @magentoConfigFixture default_store payment/multisafepay_applepay/active 0
     * @magentoConfigFixture default_store payment/multisafepay_applepay/direct_button 0
     *
     * @return void
     * @throws Exception
     */
    public function testDisabledApplePayRequest()
    {
        $quote = $this->getQuote('test01');
        $result = $this->applePayRequest->create($quote);

        self::assertNull($result);
    }

    /**
     * Test if the Apple Pay request contains all the expected data
     *
     * @magentoConfigFixture default_store payment/multisafepay_applepay/active 0
     * @magentoConfigFixture default_store payment/multisafepay_applepay/direct_button 0
     *
     * @return void
     * @throws Exception
     */
    public function testApplePayRequestWithoutQuote()
    {
        $result = $this->applePayRequest->create(null);

        self::assertNull($result);
    }
}
