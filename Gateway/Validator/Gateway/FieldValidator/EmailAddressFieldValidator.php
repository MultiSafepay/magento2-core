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
use MultiSafepay\ConnectCore\Model\Api\Validator\EmailAddressValidator;

class EmailAddressFieldValidator implements GatewayFieldValidatorInterface
{
    /**
     * @var EmailAddressValidator
     */
    private $emailAddressValidator;

    /**
     * EmailAddressFieldValidator constructor.
     *
     * @param EmailAddressValidator $emailAddressValidator
     */
    public function __construct(
        EmailAddressValidator $emailAddressValidator
    ) {
        $this->emailAddressValidator = $emailAddressValidator;
    }

    /**
     * Validate the e-mail address field
     *
     * @param array $gatewayAdditionalFieldData
     * @return bool
     */
    public function validate(array $gatewayAdditionalFieldData): bool
    {
        return isset($gatewayAdditionalFieldData['email_address'])
               && $this->emailAddressValidator->validate($gatewayAdditionalFieldData['email_address']);
    }

    /**
     * Get the validation message
     *
     * @return Phrase
     */
    public function getValidationMessage(): Phrase
    {
        return __('Please enter a valid email address (Ex: johndoe@domain.com).');
    }
}
