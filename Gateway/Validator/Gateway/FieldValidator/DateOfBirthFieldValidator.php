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

namespace MultiSafepay\ConnectCore\Gateway\Validator\Gateway\FieldValidator;

use Magento\Framework\Phrase;
use MultiSafepay\ConnectCore\Model\Api\Validator\DateOfBirthValidator;

class DateOfBirthFieldValidator implements GatewayFieldValidatorInterface
{
    /**
     * @var DateOfBirthValidator
     */
    private $dateOfBirthValidator;

    /**
     * DateOfBirthFieldValidator constructor.
     *
     * @param DateOfBirthValidator $dateOfBirthValidator
     */
    public function __construct(
        DateOfBirthValidator $dateOfBirthValidator
    ) {
        $this->dateOfBirthValidator = $dateOfBirthValidator;
    }

    /**
     * @param array $gatewayAdditionalFieldData
     * @return bool
     */
    public function validate(array $gatewayAdditionalFieldData): bool
    {
        return isset($gatewayAdditionalFieldData['date_of_birth'])
               && $this->dateOfBirthValidator->validate($gatewayAdditionalFieldData['date_of_birth']);
    }

    /**
     * @return Phrase
     */
    public function getValidationMessage(): Phrase
    {
        return __('Invalid Date of Birth');
    }
}
