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

namespace MultiSafepay\ConnectCore\Test\Integration\Model\Ui\Giftcard;

use Magento\Framework\Exception\LocalizedException;
use MultiSafepay\ConnectCore\Model\Ui\Giftcard\EdenredGiftcardConfigProvider;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EdenredGiftcardConfigProviderTest extends AbstractTestCase
{
    /**
     * @var EdenredGiftcardConfigProvider
     */
    private $edenredGiftcardConfigProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->edenredGiftcardConfigProvider = $this->getObjectManager()->get(EdenredGiftcardConfigProvider::class);
    }

    /**
     * @magentoConfigFixture default_store payment/multisafepay_edenred/edencom_categories 1,2
     * @magentoConfigFixture default_store payment/multisafepay_edenred/edeneco_categories 3
     * @magentoConfigFixture default_store payment/multisafepay_edenred/edenres_categories
     */
    public function testgetAvailableCategoriesAndCoupons(): void
    {
        self::assertEquals(
            [
                EdenredGiftcardConfigProvider::EDENCOM_COUPON_CODE => ['1', '2'],
                EdenredGiftcardConfigProvider::EDENECO_COUPON_CODE => ['3'],
            ],
            $this->edenredGiftcardConfigProvider->getAvailableCategoriesAndCoupons()
        );
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/quote_with_multiple_products.php
     * @magentoConfigFixture default_store payment/multisafepay_edenred/edencom_categories 1,2
     * @magentoConfigFixture default_store payment/multisafepay_edenred/edeneco_categories all_categories
     * @magentoConfigFixture default_store payment/multisafepay_edenred/edenres_categories 6
     * @magentoConfigFixture default_store payment/multisafepay_edenred/edensports_categories 3,6,7,8
     */
    public function testGetAvailableCouponsByQuote(): void
    {
        self::assertNotEmpty(
            $this->edenredGiftcardConfigProvider->getAvailableCouponsByQuote($this->getQuote('tableRate'))
        );
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/quote_with_multiple_products.php
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     */
    public function testGetAvailableCouponsByQuoteWithEmptyCoupons(): void
    {
        self::assertEmpty(
            $this->edenredGiftcardConfigProvider->getAvailableCouponsByQuote($this->getQuote('tableRate'))
        );
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/quote_with_multiple_products.php
     * @magentoConfigFixture default_store payment/multisafepay_edenred/edencom_categories 1,2,3,5
     * @magentoConfigFixture default_store payment/multisafepay_edenred/edenres_categories 7
     * @magentoConfigFixture default_store payment/multisafepay_edenred/edensports_categories 5,6,7,8
     */
    public function testGetAvailableCouponsByQuoteWithNonIntersectedCategories(): void
    {
        self::assertEmpty(
            $this->edenredGiftcardConfigProvider->getAvailableCouponsByQuote($this->getQuote('tableRate'))
        );
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order_with_multiple_items.php
     * @magentoConfigFixture default_store payment/multisafepay_edenred/edencom_categories 2,3,5
     * @magentoConfigFixture default_store payment/multisafepay_edenred/edenres_categories 7
     * @magentoConfigFixture default_store payment/multisafepay_edenred/edensports_categories 5,6,7,8
     *
     * @throws LocalizedException
     */
    public function testGetAvailableCouponsByOrder(): void
    {
        self::assertEquals(
            [
                EdenredGiftcardConfigProvider::EDENCOM_COUPON_CODE
            ],
            $this->edenredGiftcardConfigProvider->getAvailableCouponsByOrder(
                $this->getOrderWithVisaPaymentMethod()
            )
        );
    }
}
