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

class EmailAddressValidator
{
    /**
     * Check if the e-mail address is valid
     *
     * @param string $emailAddress
     * @return bool
     */
    public function validate(string $emailAddress): bool
    {
        return $emailAddress && filter_var($emailAddress, FILTER_VALIDATE_EMAIL);
    }
}
