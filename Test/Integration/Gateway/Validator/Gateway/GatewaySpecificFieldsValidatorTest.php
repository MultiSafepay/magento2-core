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

namespace MultiSafepay\ConnectCore\Test\Integration\Gateway\Validator\Gateway;

use Exception;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use MultiSafepay\ConnectCore\Gateway\Validator\Gateway\AfterpayValidator;
use MultiSafepay\ConnectCore\Gateway\Validator\Gateway\BaseGatewayValidator;
use MultiSafepay\ConnectCore\Gateway\Validator\Gateway\DirectBankTransferValidator;
use MultiSafepay\ConnectCore\Gateway\Validator\Gateway\DirectDebitValidator;
use MultiSafepay\ConnectCore\Gateway\Validator\Gateway\EinvoicingValidator;
use MultiSafepay\ConnectCore\Gateway\Validator\Gateway\PayafterValidator;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\AfterpayConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\DirectBankTransferConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\DirectDebitConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\EinvoicingConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\PayafterConfigProvider;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\TransactionTypeBuilder;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GatewaySpecificFieldsValidatorTest extends AbstractTestCase
{
    /**
     * @var BaseGatewayValidator
     */
    private $baseGatewayValidator;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->baseGatewayValidator = $this->getObjectManager()->create(BaseGatewayValidator::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/quote_with_multiple_products.php
     * @dataProvider         gatewayDataProvider
     *
     * @param string $class
     * @param string $paymentCode
     * @param array $additionalFields
     * @param array $expected
     * @throws Exception
     */
    public function testValidateGatewaysWithSpecificFields(
        string $class,
        string $paymentCode,
        array $additionalFields,
        array $expected
    ): void {
        /** @var BaseGatewayValidator $validator */
        $validator = $this->getObjectManager()->create($class);
        $quote = $this->getQuote('tableRate');
        $payment = $this->getPayment($paymentCode, $quote);
        $payment->setAdditionalInformation($additionalFields);
        $result = $validator->validate(['payment' => $payment]);

        self::assertEquals($result->isValid(), $expected['result']);

        if (!$result->isValid()) {
            self::assertEquals($result->getFailsDescription()[0]->render(), $expected['message']);
        }
    }

    public function testBaseGatewayValidatorWithoutPayment(): void
    {
        $result = $this->baseGatewayValidator->validate([]);

        self::assertFalse($result->isValid());
        self::assertEquals('Can\'t get a payment information', $result->getFailsDescription()[0]->render());
    }

    /**
     * @return array[]
     */
    public function gatewayDataProvider(): array
    {
        return [
            'afterpay' => [
                'class' => AfterpayValidator::class,
                'payment_code' => AfterpayConfigProvider::CODE,
                'fields' => [
                    'date_of_birth' => '1990-10-10',
                    'gender' => 'mr',
                    'phone_number' => '12314566',
                    'transaction_type' => TransactionTypeBuilder::TRANSACTION_TYPE_DIRECT_VALUE
                ],
                'expected' => [
                    'result' => true,
                    'message' => ''
                ]
            ],
            'direct_bank_transfer' => [
                'class' => DirectBankTransferValidator::class,
                'payment_code' => DirectBankTransferConfigProvider::CODE,
                'fields' => [
                    'account_number' => 'NL87ABNA0000000002',
                    'account_id' => '123',
                    'account_holder_name' => 'Test',
                    'account_holder_city' => 'Amsterdam',
                    'account_holder_country' => 'NL',
                    'account_holder_bic' => '',
                    'transaction_type' => TransactionTypeBuilder::TRANSACTION_TYPE_DIRECT_VALUE
                ],
                'expected' => [
                    'result' => false,
                    'message' => 'The account holder bic can not be empty'
                ]
            ],
            'direct_debit' => [
                'class' => DirectDebitValidator::class,
                'payment_code' => DirectDebitConfigProvider::CODE,
                'fields' => [
                    'account_number' => 'NLEQ87ABNA0000000002',
                    'account_holder_name' => 'Test',
                    'transaction_type' => TransactionTypeBuilder::TRANSACTION_TYPE_DIRECT_VALUE
                ],
                'expected' => [
                    'result' => false,
                    'message' => 'NLEQ87ABNA0000000002 is not a valid IBAN number'
                ]
            ],
            'einvoicing' => [
                'class' => EinvoicingValidator::class,
                'payment_code' => EinvoicingConfigProvider::CODE,
                'fields' => [
                    'date_of_birth' => '1990-10-10',
                    'account_number' => 'NL87ABNA0000000004',
                    'transaction_type' => TransactionTypeBuilder::TRANSACTION_TYPE_DIRECT_VALUE
                ],
                'expected' => [
                    'result' => true,
                    'message' => ''
                ]
            ],
            'payafter' => [
                'class' => PayafterValidator::class,
                'payment_code' => PayafterConfigProvider::CODE,
                'fields' => [
                    'date_of_birth' => '1990-10-10',
                    'account_number' => 'NL87ABNA0000000004',
                    'transaction_type' => TransactionTypeBuilder::TRANSACTION_TYPE_REDIRECT_VALUE
                ],
                'expected' => [
                    'result' => true,
                    'message' => ''
                ]
            ]
        ];
    }

    /**
     * @param string $method
     * @param CartInterface $quote
     * @return PaymentInterface
     */
    private function getPayment(string $method, CartInterface $quote): PaymentInterface
    {
        $payment = $quote->getPayment();
        $payment->setMethod($method);

        return $payment;
    }
}
