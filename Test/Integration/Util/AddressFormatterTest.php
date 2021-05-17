<?php

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
     * @return Address
     */
    private function getShippingAddress(): Address
    {
        /** @var $address Address */
        $address = $this->getObjectManager()->create(Address::class);

        $address->setRegion('NL')
            ->setPostcode('1033SC')
            ->setFirstname('MultiSafepayFirstName')
            ->setLastname('MultiSafepayLastName')
            ->setStreet('Kraanspoor 39')
            ->setCity('Amsterdam')
            ->setEmail('test@example.com')
            ->setTelephone('0208500500')
            ->setCountryId('NL')
            ->setAddressType('shipping');

        return $address;
    }

    /**
     * @return Address
     */
    private function getBillingAddress(): Address
    {
        /** @var $address Address */
        $address = $this->getObjectManager()->create(Address::class);

        $address->setRegion('CA')
            ->setPostcode('90210')
            ->setFirstname('TestFirstName')
            ->setLastname('TestLastName')
            ->setStreet('teststreet 50')
            ->setCity('Beverly Hills')
            ->setEmail('admin@example.com')
            ->setTelephone('1111111111')
            ->setCountryId('US')
            ->setAddressType('billing');

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

    public function testSerializeAddressShouldReturnSerializedAddress()
    {
        $result = $this->addressFormatter->serializeAddress($this->getShippingAddress());

        self::assertTrue($this->isSerialized($result));
    }

    public function testIsSameAddressShouldReturnFalseIfDifferentBillingAndShipping()
    {
        $this->assertFalse(
            $this->addressFormatter->isSameAddress(
                $this->getShippingAddress(),
                $this->getBillingAddress()
            )
        );
    }

    public function testIsSameAddressShouldReturnTrueIfSameBillingAndShipping()
    {
        $billingAddress = $this->getBillingAddress()
            ->setStreet('Kraanspoor 39')
            ->setPostcode('1033SC')
            ->setCity('Amsterdam');

        $this->assertTrue($this->addressFormatter->isSameAddress($this->getShippingAddress(), $billingAddress));
    }
}
