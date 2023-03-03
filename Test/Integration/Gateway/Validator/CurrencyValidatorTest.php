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

use Exception;
use Magento\Payment\Gateway\Config\Config as PaymentGatewayConfig;
use MultiSafepay\ConnectCore\Gateway\Validator\CurrencyValidator;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\VisaConfigProvider;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class CurrencyValidatorTest extends AbstractTestCase
{
    /**
     * @var CurrencyValidator
     */
    private $currencyValidator;

    /**
     * @var PaymentGatewayConfig
     */
    private $paymentConfig;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->currencyValidator = $this->getObjectManager()->create(CurrencyValidator::class);
        $this->paymentConfig = $this->getObjectManager()->create(PaymentGatewayConfig::class);
        $this->paymentConfig->setMethodCode(VisaConfigProvider::CODE);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/quote_with_multiple_products.php
     * @magentoConfigFixture default_store payment/multisafepay_visa/allow_specific_currency 0
     *
     * @throws Exception
     */
    public function testValidateWithAllowSpecificCurrencyDisabled(): void
    {
        self::assertFalse(
            $this->currencyValidator->validate(
                $this->getQuote('tableRate'),
                $this->paymentConfig,
                VisaConfigProvider::CODE
            )
        );
    }

    /**
     * @magentoConfigFixture default_store payment/multisafepay_visa/allow_specific_currency 1
     * @magentoConfigFixture default_store payment/multisafepay_visa/allowed_currency EUR
     *
     * @throws Exception
     */
    public function testValidateWithAllowSpecificCurrencyEnabled(): void
    {
        self::assertTrue(
            $this->currencyValidator->validate(
                $this->getQuote('tableRate'),
                $this->paymentConfig,
                VisaConfigProvider::CODE
            )
        );
    }

    /**
     * @magentoConfigFixture default_store payment/multisafepay_visa/allow_specific_currency 1
     * @magentoConfigFixture default_store payment/multisafepay_visa/allowed_currency USD,EUR
     *
     * @throws Exception
     */
    public function testValidatePassedWithAllowSpecificCurrencyEnabled(): void
    {
        self::assertFalse(
            $this->currencyValidator->validate(
                $this->getQuote('tableRate'),
                $this->paymentConfig,
                VisaConfigProvider::CODE
            )
        );
    }
}
