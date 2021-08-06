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

use MultiSafepay\ConnectCore\Model\Api\Validator\DateOfBirthValidator;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class DateOfBitrhValidatorTest extends AbstractTestCase
{
    /**
     * @var DateOfBirthValidator
     */
    private $dateOfBirthValidator;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->dateOfBirthValidator = $this->getObjectManager()->create(DateOfBirthValidator::class);
    }

    /**
     * @dataProvider dateOfBirthValidatorDataProvider
     *
     * @param string $dateOfBirth
     * @param bool $expected
     */
    public function testDateOfBirthValidatorWithDifferentValues(string $dateOfBirth, bool $expected): void
    {
        self::assertEquals($expected, $this->dateOfBirthValidator->validate($dateOfBirth));
    }

    /**
     * @return array[]
     */
    public function dateOfBirthValidatorDataProvider(): array
    {
        return [
            ['1990-12-12', true],
            ['12.12.1990', false],
            ['12/12/1990', false],
            ['12.31.1990', false],
            ['45.12.1990', false],
            ['test_date_of_birth', false],
            ['12-12-199h', false],
            ['', false]
        ];
    }
}
