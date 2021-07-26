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

use Magento\Quote\Api\Data\CartInterface;
use MultiSafepay\ConnectCore\Util\AddressFormatter;

class AddressValidator
{
    /**
     * @var AddressFormatter
     */
    private $addressFormatter;

    /**
     * AddressValidator constructor.
     *
     * @param AddressFormatter $addressFormatter
     */
    public function __construct(
        AddressFormatter $addressFormatter
    ) {
        $this->addressFormatter = $addressFormatter;
    }

    /**
     * @param  $quote
     * @return bool
     */
    public function validate($quote): bool
    {
        return $this->addressFormatter->isSameAddress($quote->getShippingAddress(), $quote->getBillingAddress());
    }
}
