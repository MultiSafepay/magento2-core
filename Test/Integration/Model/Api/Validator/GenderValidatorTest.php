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

use MultiSafepay\ConnectCore\Model\Api\Validator\GenderValidator;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class GenderValidatorTest extends AbstractTestCase
{
    /**
     * @var GenderValidator
     */
    private $genderValidator;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->genderValidator = $this->getObjectManager()->create(GenderValidator::class);
    }

    /**
     * @dataProvider genderValidatorDataProvider
     *
     * @param string $genderValue
     * @param bool $expected
     */
    public function testGenderValidatorWithDifferentValues(string $genderValue, bool $expected): void
    {
        self::assertEquals($expected, $this->genderValidator->validate($genderValue));
    }

    /**
     * @return array[]
     */
    public function genderValidatorDataProvider(): array
    {
        return [
            ['mr', true],
            ['mrs', true],
            ['miss', true],
            ['mr, mrs', false],
            ['mrsss', false],
            ['test_gender', false],
            ['mis', false],
            ['', false]
        ];
    }
}
