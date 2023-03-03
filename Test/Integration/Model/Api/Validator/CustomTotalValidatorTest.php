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
use Magento\Quote\Api\Data\TotalsInterface;
use MultiSafepay\ConnectCore\Model\Api\Validator\CustomTotalValidator;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class CustomTotalValidatorTest extends AbstractTestCase
{
    /**
     * @var CustomTotalValidator
     */
    private $customTotalValidator;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->customTotalValidator = $this->getObjectManager()->create(CustomTotalValidator::class);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote_with_multiple_products.php
     *
     * @throws Exception
     */
    public function testCustomTotalValidator(): void
    {
        $grandTotal = $this->getQuote('tableRate')->getTotals()[TotalsInterface::KEY_GRAND_TOTAL];

        self::assertTrue($this->customTotalValidator->validate($grandTotal));

        $grandTotal->setValue(null);

        self::assertFalse($this->customTotalValidator->validate($grandTotal));
    }
}
