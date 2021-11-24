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

use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\GooglePayConfigProvider;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GooglePayConfigProviderTest extends AbstractTestCase
{
    /**
     * @var GooglePayConfigProvider
     */
    private $googlePayConfigProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->googlePayConfigProvider = $this->getObjectManager()->get(GooglePayConfigProvider::class);
    }

    /**
     * @magentoConfigFixture default_store payment/multisafepay_googlepay/direct_button_mode 1
     * @magentoConfigFixture default_store payment/multisafepay_googlepay/active 1
     */
    public function testGetGooglePlayMode(): void
    {
        self::assertSame(
            GooglePayConfigProvider::GOOGLE_PAY_PRODUCTION_MODE,
            $this->googlePayConfigProvider->getGooglePayMode()
        );
    }

    /**
     * @magentoConfigFixture default_store multisafepay/general/account_data {"account_id":12343565,"role":"merchant","site_id":123123}
     * @magentoConfigFixture default_store payment/multisafepay_googlepay/direct_button 1
     * @magentoConfigFixture default_store payment/multisafepay_googlepay/active 1
     */
    public function testGetMultisafepayAccountId(): void
    {
        self::assertSame('12343565', $this->googlePayConfigProvider->getMultisafepayAccountId());
    }

    public function testGetMultisafepayAccountIdEmpty(): void
    {
        self::assertSame('', $this->googlePayConfigProvider->getMultisafepayAccountId());
    }

    /**
     * @magentoConfigFixture default_store payment/multisafepay_googlepay/direct_button_merchant_name test_name
     * @magentoConfigFixture default_store payment/multisafepay_googlepay/direct_button_merchant_id test_merchant_id
     * @magentoConfigFixture default_store payment/multisafepay_googlepay/direct_button 1
     * @magentoConfigFixture default_store payment/multisafepay_googlepay/active 1
     */
    public function testGetGooglePayMerchantInfo(): void
    {
        self::assertEquals(
            [
                'merchantName' => 'test_name',
                'merchantId' => 'test_merchant_id',
            ],
            $this->googlePayConfigProvider->getGooglePayMerchantInfo()
        );
    }

    public function testGetGooglePayMerchantInfoEmpty(): void
    {
        self::assertEquals(
            [
                'merchantName' => '',
                'merchantId' => '',
            ],
            $this->googlePayConfigProvider->getGooglePayMerchantInfo()
        );
    }
}
