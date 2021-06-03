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
use MultiSafepay\ConnectCore\Gateway\Validator\AmountValidator;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\VisaConfigProvider;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class AmountValidatorTest extends AbstractTestCase
{
    /**
     * @var AmountValidator
     */
    private $amountValidator;

    /**
     * @var PaymentGatewayConfig
     */
    private $paymentConfig;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->amountValidator = $this->getObjectManager()->create(AmountValidator::class);
        $this->paymentConfig = $this->getObjectManager()->create(PaymentGatewayConfig::class);
        $this->paymentConfig->setMethodCode(VisaConfigProvider::CODE);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/quote_with_multiple_products.php
     * @magentoConfigFixture default_store payment/multisafepay_visa/allow_amount 0
     *
     * @throws Exception
     */
    public function testValidateWithAllowSpecificAmountDisabled(): void
    {
        self::assertFalse(
            $this->amountValidator->validate($this->getQuote('tableRate'), $this->paymentConfig)
        );
    }

    /**
     * @magentoConfigFixture default_store payment/multisafepay_visa/allow_amount 1
     * @magentoConfigFixture default_store payment/multisafepay_visa/min_amount 150
     * @magentoConfigFixture default_store payment/multisafepay_visa/max_amount 300
     *
     * @throws Exception
     */
    public function testValidateWithAllowSpecificAmountEnabled(): void
    {
        self::assertTrue(
            $this->amountValidator->validate($this->getQuote('tableRate'), $this->paymentConfig)
        );
    }

    /**
     * @magentoConfigFixture default_store payment/multisafepay_visa/allow_amount 1
     * @magentoConfigFixture default_store payment/multisafepay_visa/min_amount 50
     * @magentoConfigFixture default_store payment/multisafepay_visa/max_amount 150
     *
     * @throws Exception
     */
    public function testValidatePassedWithAllowSpecificAmountEnabled(): void
    {
        self::assertFalse(
            $this->amountValidator->validate($this->getQuote('tableRate'), $this->paymentConfig)
        );
    }

    /**
     * @magentoConfigFixture default_store payment/multisafepay_visa/allow_amount 1
     *
     * @throws Exception
     */
    public function testValidateWithAllowSpecificAmountNotSetted(): void
    {
        self::assertFalse(
            $this->amountValidator->validate($this->getQuote('tableRate'), $this->paymentConfig)
        );
    }

    /**
     * @magentoConfigFixture default_store payment/multisafepay_visa/allow_amount 1
     * @magentoConfigFixture default_store payment/multisafepay_visa/min_amount 50
     * @magentoConfigFixture default_store payment/multisafepay_visa/max_amount
     *
     * @throws Exception
     */
    public function testValidateWithAllowSpecificAmountWithMaxAmountNotSetted(): void
    {
        self::assertFalse(
            $this->amountValidator->validate($this->getQuote('tableRate'), $this->paymentConfig)
        );
    }
}
