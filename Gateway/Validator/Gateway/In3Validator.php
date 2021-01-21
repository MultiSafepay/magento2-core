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
use MultiSafepay\ConnectCore\Model\Api\Validator\DateOfBirthValidator;
use MultiSafepay\ConnectCore\Model\Api\Validator\GenderValidator;

class In3Validator extends AbstractValidator
{
    /**
     * @var DateOfBirthValidator
     */
    private $dateOfBirthValidator;

    /**
     * @var GenderValidator
     */
    private $genderValidator;

    /**
     * In3Validator constructor.
     *
     * @param DateOfBirthValidator $dateOfBirthValidator
     * @param GenderValidator $genderValidator
     * @param ResultInterfaceFactory $resultFactory
     */
    public function __construct(
        DateOfBirthValidator $dateOfBirthValidator,
        GenderValidator $genderValidator,
        ResultInterfaceFactory $resultFactory
    ) {
        $this->dateOfBirthValidator = $dateOfBirthValidator;
        $this->genderValidator = $genderValidator;
        parent::__construct($resultFactory);
    }

    /**
     * @inheritDoc
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $payment = $validationSubject['payment'];

        if (!array_key_exists('date_of_birth', $payment->getAdditionalInformation())) {
            return $this->createResult(false, [__('Invalid Date of Birth')]);
        }

        if (!$this->dateOfBirthValidator->validate($payment->getAdditionalInformation()['date_of_birth'])) {
            return $this->createResult(false, [__('Invalid Date of Birth')]);
        }

        if (!array_key_exists('gender', $payment->getAdditionalInformation())) {
            return $this->createResult(false, [__('Please choose a gender')]);
        }

        if (!$this->genderValidator->validate($payment->getAdditionalInformation()['gender'])) {
            return $this->createResult(false, [__('Please choose a gender')]);
        }

        return $this->createResult(true);
    }
}
