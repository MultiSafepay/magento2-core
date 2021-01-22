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

namespace MultiSafepay\ConnectCore\Util;

use Magento\Framework\Serialize\SerializerInterface;

class AddressFormatter
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * AddressHelper constructor.
     *
     * @param SerializerInterface $serializer
     */
    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
    }

    /**
     * @param $address
     * @return bool|string
     */
    public function serializeAddress($address)
    {
        return $this->serializer->serialize([
            'street' => $address->getStreet(),
            'city' => $address->getCity(),
            'postcode' => $address->getPostcode()
        ]);
    }

    /**
     * @param $shippingAddress
     * @param $billingAddress
     * @return bool
     */
    public function isSameAddress($shippingAddress, $billingAddress): bool
    {
        return $this->serializeAddress($shippingAddress) === $this->serializeAddress($billingAddress);
    }
}
