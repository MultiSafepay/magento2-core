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

namespace MultiSafepay\ConnectCore\Gateway\Validator\Gateway\FieldValidator;

use Magento\Framework\Phrase;
use MultiSafepay\ConnectCore\Model\Api\Validator\AccountNumberValidator;

class BankAccountNumberFieldValidator implements GatewayFieldValidatorInterface
{
    /**
     * @var string
     */
    private $accountNumber = '';

    /**
     * @var AccountNumberValidator
     */
    private $accountNumberValidator;

    /**
     * BankAccountNumberFieldValidator constructor.
     *
     * @param AccountNumberValidator $accountNumberValidator
     */
    public function __construct(
        AccountNumberValidator $accountNumberValidator
    ) {
        $this->accountNumberValidator = $accountNumberValidator;
    }

    /**
     * @param array $gatewayAdditionalFieldData
     * @return bool
     */
    public function validate(array $gatewayAdditionalFieldData): bool
    {
        $possibleFieldNames = [
            'account_number',
            'account_holder_iban',
        ];

        foreach ($possibleFieldNames as $fieldName) {
            if (isset($gatewayAdditionalFieldData[$fieldName])) {
                $this->accountNumber = $gatewayAdditionalFieldData[$fieldName];

                return $this->accountNumberValidator->validate($gatewayAdditionalFieldData[$fieldName]);
            }
        }

        return false;
    }

    /**
     * @return Phrase
     */
    public function getValidationMessage(): Phrase
    {
        return $this->accountNumber ? __('%1 is not a valid IBAN number', $this->accountNumber)
            : __('IBAN number can not be empty');
    }
}
