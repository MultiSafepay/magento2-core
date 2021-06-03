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
use Magento\Customer\Model\Session;
use Magento\Payment\Gateway\Config\Config as PaymentGatewayConfig;
use MultiSafepay\ConnectCore\Gateway\Validator\CustomerGroupValidator;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\VisaConfigProvider;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class CustomerGroupValidatorTest extends AbstractTestCase
{
    /**
     * @var CustomerGroupValidator
     */
    private $customerGroupValidator;

    /**
     * @var PaymentGatewayConfig
     */
    private $paymentConfig;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->customerGroupValidator = $this->getObjectManager()->create(CustomerGroupValidator::class);
        $this->customerSession = $this->getObjectManager()->create(Session::class);
        $this->paymentConfig = $this->getObjectManager()->create(PaymentGatewayConfig::class);
        $this->paymentConfig->setMethodCode(VisaConfigProvider::CODE);
        $this->customerSession->setCustomerId(null);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/quote_with_multiple_products.php
     * @magentoConfigFixture default_store payment/multisafepay_visa/allow_specific_customer_group 0
     *
     * @throws Exception
     */
    public function testValidateWithAllowSpecificCustomerGroupsDisabled(): void
    {
        /** @var CustomerGroupValidator $customerGroupValidator */
        self::assertFalse(
            $this->customerGroupValidator->validate(
                $this->getQuote('tableRate'),
                $this->paymentConfig
            )
        );
    }

    /**
     * @magentoDataFixture   Magento/Customer/_files/customer.php
     * @magentoConfigFixture default_store payment/multisafepay_visa/allow_specific_customer_group 1
     * @magentoConfigFixture default_store payment/multisafepay_visa/allowed_customer_group 3,4
     * @magentoConfigFixture default_store customer/create_account/default_group 1
     *
     * @throws Exception
     */
    public function testValidateWithAllowSpecificCustomerGroupsEnabled(): void
    {
        $quote = $this->getQuote('tableRate');
        $quote->setCustomerGroupId(1);

        self::assertTrue(
            $this->customerGroupValidator->validate(
                $quote,
                $this->paymentConfig
            )
        );
    }

    /**
     * @magentoDataFixture   Magento/Customer/_files/customer.php
     * @magentoConfigFixture default_store payment/multisafepay_visa/allow_specific_customer_group 1
     * @magentoConfigFixture default_store payment/multisafepay_visa/allowed_customer_group 1
     * @magentoConfigFixture default_store customer/create_account/default_group 1
     *
     * @throws Exception
     */
    public function testValidatePassedWithAllowSpecificCustomerGroupsEnabled(): void
    {
        $quote = $this->getQuote('tableRate');
        $quote->setCustomerGroupId(1);
        $this->customerSession->setCustomerId(1);

        self::assertFalse(
            $this->customerGroupValidator->validate(
                $quote,
                $this->paymentConfig
            )
        );
    }
}
