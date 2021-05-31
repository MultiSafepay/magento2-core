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

namespace MultiSafepay\ConnectCore\Test\Integration\Model\Api\Validator;

use MultiSafepay\ConnectCore\Model\Api\Validator\AccountNumberValidator;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class AccountNumberValidatorTest extends AbstractTestCase
{
    /**
     * @var AccountNumberValidator
     */
    private $accountNumberValidator;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->accountNumberValidator = $this->getObjectManager()->create(AccountNumberValidator::class);
    }

    /**
     * @dataProvider accountNumberDataProvider
     *
     * @param string $accountNumber
     * @param bool $expected
     */
    public function testAccountNumberValidatorWithDifferentValues(string $accountNumber, bool $expected): void
    {
        self::assertEquals($expected, $this->accountNumberValidator->validate($accountNumber));
    }

    /**
     * @return array[]
     */
    public function accountNumberDataProvider(): array
    {
        return [
            ['NL87ABNA0000000002', true],
            ['NL87123ABNA0000000002', true],
            ['NLAS87123ABNA0000000002', false],
            ['test_number', false],
            ['', false],
            ['AP11ING123123123', true]
        ];
    }
}
