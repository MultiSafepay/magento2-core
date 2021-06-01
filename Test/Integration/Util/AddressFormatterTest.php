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
 * Copyright © 2020 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Test\Integration\Util;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Model\Order\Address;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\AddressFormatter;

class AddressFormatterTest extends AbstractTestCase
{
    /**
     * @var AddressFormatter
     */
    private $addressFormatter;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->addressFormatter = $this->getObjectManager()->create(AddressFormatter::class);
    }

    /**
     * @param string $type
     * @return Address
     */
    private function getAddressByType(string $type = 'shipping'): Address
    {
        /** @var $address Address */
        $address = $this->getObjectManager()->create(Address::class);
        $addressData = include __DIR__ . '/../_files/address_data.php';
        $address->setData($addressData[$type]);

        return $address;
    }

    /**
     * @param $string
     * @return bool
     */
    private function isSerialized($string): bool
    {
        return $this->getObjectManager()->create(SerializerInterface::class)->unserialize($string) !== false;
    }

    public function testSerializeAddressShouldReturnSerializedAddress(): void
    {
        $result = $this->addressFormatter->serializeAddress($this->getAddressByType());

        self::assertTrue($this->isSerialized($result));
    }

    public function testIsSameAddressShouldReturnFalseIfDifferentBillingAndShipping(): void
    {
        self::assertFalse(
            $this->addressFormatter->isSameAddress(
                $this->getAddressByType(),
                $this->getAddressByType('billing')
            )
        );
    }

    public function testIsSameAddressShouldReturnTrueIfSameBillingAndShipping(): void
    {
        $billingAddress = $this->getAddressByType('billing')
            ->setStreet('Kraanspoor 39')
            ->setPostcode('1033SC')
            ->setCity('Amsterdam');

        self::assertTrue($this->addressFormatter->isSameAddress($this->getAddressByType(), $billingAddress));
    }
}
