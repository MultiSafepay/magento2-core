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

namespace MultiSafepay\ConnectCore\Test\Integration\Model;

use Exception;
use InvalidArgumentException;
use MultiSafepay\ConnectCore\Model\SecureToken;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class SecureTokenTest extends AbstractTestCase
{
    private const TEST_ORIGINAL_VALUE = 'test_original_value';
    private const TEST_ORIGINAL_VALUE_RESULT_TOKEN = 'a3f063403e62e20ac64ea8a7d96bcd81887143ce61799729b217725358b6bbad';

    /**
     * @var SecureToken
     */
    private $secureToken;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->secureToken = $this->getObjectManager()->create(SecureToken::class);
    }

    /**
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     */
    public function testGenerateTokenWithApiTokenSet(): void
    {
        $token = $this->secureToken->generate(self::TEST_ORIGINAL_VALUE);

        self::assertSame(self::TEST_ORIGINAL_VALUE_RESULT_TOKEN, $token);

        $this->expectExceptionMessage('Original value param can\'t be empty');
        $this->expectException(InvalidArgumentException::class);

        self::assertNotEmpty($this->secureToken->generate(''));
    }

    /**
     * @throws Exception
     */
    public function testGenerateTokenWithoutApiToken(): void
    {
        $this->expectExceptionMessage('No API key configured');
        $this->expectException(InvalidArgumentException::class);

        $this->secureToken->generate(self::TEST_ORIGINAL_VALUE);
    }

    /**
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     */
    public function testValidateToken(): void
    {
        self::assertTrue(
            $this->secureToken->validate(self::TEST_ORIGINAL_VALUE, self::TEST_ORIGINAL_VALUE_RESULT_TOKEN)
        );

        self::assertTrue(
            $this->secureToken->validate('test_original_value_2', self::TEST_ORIGINAL_VALUE_RESULT_TOKEN)
        );
    }

    /**
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey_2
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     */
    public function testValidateTokenWithDifferentApiKey(): void
    {
        self::assertFalse(
            $this->secureToken->validate(self::TEST_ORIGINAL_VALUE, self::TEST_ORIGINAL_VALUE_RESULT_TOKEN)
        );
    }
}
