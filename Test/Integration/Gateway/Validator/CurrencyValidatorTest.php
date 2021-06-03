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
 * Copyright © 2021 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
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
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $paymentConfig = $this->getObjectManager()->create(PaymentGatewayConfig::class);
        $paymentConfig->setMethodCode(VisaConfigProvider::CODE);
        $this->currencyValidator = $this->getObjectManager()->create(
            CurrencyValidator::class,
            ['config' => $paymentConfig]
        );
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/quote_with_multiple_products.php
     * @magentoConfigFixture default_store payment/multisafepay_visa/allow_specific_currency 0
     *
     * @throws Exception
     */
    public function testValidateWithAllowSpecificCurrencyDisabled(): void
    {
        self::assertTrue($this->currencyValidator->validate($this->getValidationSubjectFromQuote())->isValid());
    }

    /**
     * @magentoConfigFixture default_store payment/multisafepay_visa/allow_specific_currency 1
     * @magentoConfigFixture default_store payment/multisafepay_visa/allowed_currency EUR
     *
     * @throws Exception
     */
    public function testValidateWithAllowSpecificCurrencyEnabled(): void
    {
        $validationSubject = $this->getValidationSubjectFromQuote();

        self::assertFalse($this->currencyValidator->validate($validationSubject)->isValid());

        $validationSubject['currency'] = '';

        self::assertFalse($this->currencyValidator->validate($validationSubject)->isValid());
    }

    /**
     * @magentoConfigFixture default_store payment/multisafepay_visa/allow_specific_currency 1
     * @magentoConfigFixture default_store payment/multisafepay_visa/allowed_currency USD,EUR
     *
     * @throws Exception
     */
    public function testValidatePassedWithAllowSpecificCurrencyEnabled(): void
    {
        self::assertTrue($this->currencyValidator->validate($this->getValidationSubjectFromQuote())->isValid());
    }

    /**
     * @return array
     * @throws Exception
     */
    private function getValidationSubjectFromQuote(): array
    {
        $quote = $this->getQuote('tableRate');

        return [
            'storeId' => $quote->getStoreId(),
            'currency' => $quote->getStore()->getBaseCurrencyCode(),
        ];
    }
}
