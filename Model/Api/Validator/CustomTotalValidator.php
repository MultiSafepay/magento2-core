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

class CustomTotalValidator
{

    /**
     * @param $total
     * @return bool
     */
    public function validate($total): bool
    {
        if ($total->getCode() === null) {
            return false;
        }

        if (!$total->getTitle() && !$total->getLabel()) {
            return false;
        }

        if (empty($total->getValue()) && empty($total->getAmount())) {
            return false;
        }
        return true;
    }
}
