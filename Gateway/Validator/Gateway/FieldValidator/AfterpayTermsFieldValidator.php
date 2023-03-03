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

class AfterpayTermsFieldValidator implements GatewayFieldValidatorInterface
{

    public const AFTERPAY_TERMS_FIELDS_KEY_NAME = 'afterpay_terms';

    /**
     * @var string
     */
    private $currentFieldName = '';

    /**
     * @param array $gatewayAdditionalFieldData
     * @return bool
     */
    public function validate(array $gatewayAdditionalFieldData): bool
    {
        if (isset($gatewayAdditionalFieldData[self::AFTERPAY_TERMS_FIELDS_KEY_NAME])) {
            return (bool) $gatewayAdditionalFieldData[self::AFTERPAY_TERMS_FIELDS_KEY_NAME];
        }

        return false;
    }

    /**
     * @return Phrase
     */
    public function getValidationMessage(): Phrase
    {
        return __('The Afterpay payment terms must be accepted', $this->getMessageFieldName());
    }

    /**
     * @return string
     */
    private function getMessageFieldName(): string
    {
        return $this->currentFieldName ? str_replace('_', ' ', $this->currentFieldName)
            : '';
    }
}
