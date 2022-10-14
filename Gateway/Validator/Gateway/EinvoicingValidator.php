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
 * Copyright © 2022 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Gateway\Validator\Gateway;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Gateway\Validator\Gateway\FieldValidator\BankAccountNumberFieldValidator;
use MultiSafepay\ConnectCore\Gateway\Validator\Gateway\FieldValidator\DateOfBirthFieldValidator;
use MultiSafepay\ConnectCore\Gateway\Validator\Gateway\FieldValidator\EmailAddressFieldValidator;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\TransactionTypeBuilder;

class EinvoicingValidator extends AbstractValidator
{
    /**
     * @var DateOfBirthFieldValidator
     */
    private $dateOfBirthValidator;

    /**
     * @var BankAccountNumberFieldValidator
     */
    private $accountNumberValidator;

    /**
     * @var EmailAddressFieldValidator
     */
    private $emailAddressValidator;

    /**
     * EinvoicingValidator constructor.
     *
     * @param DateOfBirthFieldValidator $dateOfBirthValidator
     * @param BankAccountNumberFieldValidator $accountNumberValidator
     * @param EmailAddressFieldValidator $emailAddressValidator
     * @param ResultInterfaceFactory $resultFactory
     */
    public function __construct(
        DateOfBirthFieldValidator $dateOfBirthValidator,
        BankAccountNumberFieldValidator $accountNumberValidator,
        EmailAddressFieldValidator $emailAddressValidator,
        ResultInterfaceFactory $resultFactory
    ) {
        $this->dateOfBirthValidator = $dateOfBirthValidator;
        $this->accountNumberValidator = $accountNumberValidator;
        $this->emailAddressValidator = $emailAddressValidator;
        parent::__construct($resultFactory);
    }

    /**
     * Get the configured checkout fields
     *
     * @param MethodInterface $payment
     * @return array
     */
    private function getCheckoutFields(MethodInterface $payment): array
    {
        $checkoutFields = $payment->getConfigData(Config::CHECKOUT_FIELDS, $payment->getStore()) ?? '';

        if ($checkoutFieldsArray = explode(',', $checkoutFields)) {
            return $checkoutFieldsArray;
        }

        return [];
    }

    /**
     * Validate the checkout field input
     *
     * @param array $validationSubject
     * @return ResultInterface
     * @throws LocalizedException
     */
    public function validate(array $validationSubject): ResultInterface
    {
        /** @var Payment $payment */
        $payment = $validationSubject['payment'] ?? null;

        if (!$payment) {
            return $this->createResult(false, [__('Can\'t get the payment information')]);
        }

        $transactionType = $payment->getMethodInstance()->getConfigData('transaction_type', $payment->getStore());

        if ($transactionType === TransactionTypeBuilder::TRANSACTION_TYPE_REDIRECT_VALUE) {
            return $this->createResult(true);
        }

        $checkoutFields = $this->getCheckoutFields($payment->getMethodInstance());

        if (in_array('date_of_birth', $checkoutFields, true)
            && !$this->dateOfBirthValidator->validate($payment->getAdditionalInformation())) {
                return $this->createResult(false, [$this->dateOfBirthValidator->getValidationMessage()]);
        }

        if (in_array('account_number', $checkoutFields, true)
            && !$this->accountNumberValidator->validate($payment->getAdditionalInformation())) {
                return $this->createResult(false, [$this->accountNumberValidator->getValidationMessage()]);
        }

        if (in_array('email_address', $checkoutFields, true)
            && !$this->emailAddressValidator->validate($payment->getAdditionalInformation())) {
            return $this->createResult(false, [$this->emailAddressValidator->getValidationMessage()]);
        }

        return $this->createResult(true);
    }
}
