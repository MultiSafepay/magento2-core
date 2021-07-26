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

namespace MultiSafepay\ConnectCore\Model\Api\Validator;

class GenderValidator
{
    public const ACCEPTED_GENDERS = [
        'mr',
        'mrs',
        'miss'
    ];

    /**
     * @param string $gender
     * @return bool
     */
    public function validate(string $gender): bool
    {
        return in_array($gender, self::ACCEPTED_GENDERS);
    }
}
