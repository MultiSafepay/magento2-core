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

namespace MultiSafepay\ConnectCore\Test\Integration\Gateway\Validator;

use Magento\Payment\Gateway\Config\Config as PaymentGatewayConfig;
use Magento\Quote\Api\Data\CartInterface;
use MultiSafepay\ConnectCore\Gateway\Validator\CategoryValidator;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\VisaConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Giftcard\EdenredGiftcardConfigProvider;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CategoryValidatorTest extends AbstractTestCase
{
    /**
     * @var CategoryValidator
     */
    private $categoryValidator;

    /**
     * @var PaymentGatewayConfig
     */
    private $paymentConfig;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->paymentConfig = $this->getObjectManager()->create(PaymentGatewayConfig::class);
        $this->paymentConfig->setMethodCode(EdenredGiftcardConfigProvider::CODE);
        $this->categoryValidator = $this->getObjectManager()->create(CategoryValidator::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/quote_with_multiple_products.php
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     */
    public function testValidateForEdenredPaymentMethod(): void
    {
        $quote = $this->getQuote('tableRate');

        self::assertTrue(
            $this->getMockBuilder(CategoryValidator::class)
                ->setConstructorArgs([
                    $this->getEdenredGiftcardConfigProviderMock($quote, []),
                ])
                ->setMethodsExcept(['validate'])
                ->getMock()->validate($quote, $this->paymentConfig, EdenredGiftcardConfigProvider::CODE)
        );

        self::assertFalse(
            $this->getMockBuilder(CategoryValidator::class)
                ->setConstructorArgs([
                    $this->getEdenredGiftcardConfigProviderMock(
                        $quote,
                        [
                            EdenredGiftcardConfigProvider::EDENCOM_COUPON_CODE,
                        ]
                    ),
                ])
                ->setMethodsExcept(['validate'])
                ->getMock()->validate($quote, $this->paymentConfig, EdenredGiftcardConfigProvider::CODE)
        );
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/quote_with_multiple_products.php
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     */
    public function testValidateForNotAvailablePaymentMethods(): void
    {
        self::assertFalse(
            $this->categoryValidator->validate(
                $this->getQuote('tableRate'),
                $this->paymentConfig,
                VisaConfigProvider::CODE
            )
        );
    }

    /**
     * @param CartInterface $quote
     * @param array $coupons
     * @return MockObject
     */
    private function getEdenredGiftcardConfigProviderMock(
        CartInterface $quote,
        array $coupons
    ): MockObject {
        $edenredGiftcardConfigProvider = $this->getMockBuilder(EdenredGiftcardConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $edenredGiftcardConfigProvider
            ->method('getAvailableCouponsByQuote')
            ->with($quote)
            ->willReturn($coupons);

        return $edenredGiftcardConfigProvider;
    }
}
