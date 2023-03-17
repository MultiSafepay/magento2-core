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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\CustomerBuilder;

use Magento\Sales\Api\Data\OrderAddressInterface;
use MultiSafepay\ValueObject\Customer\Address;
use MultiSafepay\ValueObject\Customer\AddressParser;

class AddressBuilder
{

    /**
     * @var AddressParser
     */
    private $addressParser;

    /**
     * Address constructor.
     *
     * @param AddressParser $addressParser
     */
    public function __construct(
        AddressParser $addressParser
    ) {
        $this->addressParser = $addressParser;
    }

    /**
     * @param OrderAddressInterface $address
     * @return Address
     */
    public function build(OrderAddressInterface $address): Address
    {
        $orderRequestAddress = new Address();

        $streetAndHouseNumber = $this->addressParser->parse(
            rtrim(implode(' ', $address->getStreet()))
        );

        if ($address->getRegion() !== null) {
            $orderRequestAddress->addState($address->getRegion());
        }

        return $orderRequestAddress->addCity($address->getCity() ?? '')
            ->addCountryCode($address->getCountryId())
            ->addHouseNumber($streetAndHouseNumber[1] ?? '')
            ->addStreetName($streetAndHouseNumber[0] ?? '')
            ->addZipCode(trim($address->getPostcode() ?? ''));
    }
}
