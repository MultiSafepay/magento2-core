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

namespace MultiSafepay\ConnectCore\Test\Integration\Model\Api\Validator;

use Exception;
use MultiSafepay\ConnectCore\Model\Api\Validator\AddressValidator;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class AddressValidatorTest extends AbstractTestCase
{
    /**
     * @var AddressValidator
     */
    private $addressValidator;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->addressValidator = $this->getObjectManager()->create(AddressValidator::class);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote_with_multiple_products.php
     *
     * @throws Exception
     */
    public function testQuoteAddressValidatorWithDifferentValues(): void
    {
        $quote = $this->getQuote('tableRate');
        $shippingAddress = $quote->getShippingAddress();

        self::assertTrue($this->addressValidator->validate($quote));

        $shippingAddress->setCountryId('NL')->setCity('Amsterdam');
        $quote->setShippingAddress($shippingAddress);

        self::assertFalse($this->addressValidator->validate($quote));

        $shippingAddress->setData([]);
        $quote->setShippingAddress($shippingAddress);

        self::assertFalse($this->addressValidator->validate($quote));
    }
}
