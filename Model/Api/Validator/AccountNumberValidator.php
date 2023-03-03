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

namespace MultiSafepay\ConnectCore\Model\Api\Validator;

use MultiSafepay\Exception\InvalidArgumentException;
use MultiSafepay\ValueObject\IbanNumber;

class AccountNumberValidator
{
    /**
     * @param string $accountNumber
     * @return bool
     */
    public function validate(string $accountNumber): bool
    {
        try {
            new IbanNumber($accountNumber);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return false;
        }
        return true;
    }
}
