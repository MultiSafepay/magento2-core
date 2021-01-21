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

namespace MultiSafepay\ConnectCore\Gateway\Validator\Gateway;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use MultiSafepay\ConnectCore\Model\Api\Validator\AccountNumberValidator;
use MultiSafepay\ConnectCore\Model\Api\Validator\AddressValidator;
use MultiSafepay\ConnectCore\Model\Api\Validator\DateOfBirthValidator;

class PayafterValidator extends AbstractValidator
{

    /**
     * @var AccountNumberValidator
     */
    private $accountNumberValidator;

    /**
     * @var AddressValidator
     */
    private $addressValidator;

    /**
     * @var DateOfBirthValidator
     */
    private $dateOfBirthValidator;

    /**
     * PayafterValidator constructor.
     *
     * @param AccountNumberValidator $accountNumberValidator
     * @param AddressValidator $addressValidator
     * @param DateOfBirthValidator $dateOfBirthValidator
     * @param ResultInterfaceFactory $resultFactory
     */
    public function __construct(
        AccountNumberValidator $accountNumberValidator,
        AddressValidator $addressValidator,
        DateOfBirthValidator $dateOfBirthValidator,
        ResultInterfaceFactory $resultFactory
    ) {
        $this->addressValidator = $addressValidator;
        $this->accountNumberValidator = $accountNumberValidator;
        $this->dateOfBirthValidator = $dateOfBirthValidator;
        parent::__construct($resultFactory);
    }

    /**
     * @inheritDoc
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $payment = $validationSubject['payment'];

        if (($quote = $payment->getQuote()) === null) {
            $quote = $payment->getOrder();
        }

        if (!$this->addressValidator->validate($quote)) {
            $msg = __('This gateway does not allow a different billing and shipping address');
            return $this->createResult(false, [$msg]);
        }

        if (!$this->dateOfBirthValidator->validate($payment->getAdditionalInformation()['date_of_birth'])) {
            return $this->createResult(false, [__('Invalid Date of Birth')]);
        }

        $accountNumber = $payment->getAdditionalInformation()['account_number'];

        if (!$this->accountNumberValidator->validate($accountNumber)) {
            return $this->createResult(false, [$accountNumber . __(' is not a valid IBAN number')]);
        }
        return $this->createResult(true);
    }
}
