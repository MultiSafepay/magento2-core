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
use MultiSafepay\ConnectCore\Model\Api\Validator\GenderValidator;

class GenderFieldValidator implements GatewayFieldValidatorInterface
{
    /**
     * @var GenderValidator
     */
    private $genderValidator;

    /**
     * GenderFieldValidator constructor.
     *
     * @param GenderValidator $genderValidator
     */
    public function __construct(
        GenderValidator $genderValidator
    ) {
        $this->genderValidator = $genderValidator;
    }

    /**
     * @param array $gatewayAdditionalFieldData
     * @return bool
     */
    public function validate(array $gatewayAdditionalFieldData): bool
    {
        return isset($gatewayAdditionalFieldData['gender'])
               && $this->genderValidator->validate($gatewayAdditionalFieldData['gender']);
    }

    /**
     * @return Phrase
     */
    public function getValidationMessage(): Phrase
    {
        return __('Please choose a gender');
    }
}
