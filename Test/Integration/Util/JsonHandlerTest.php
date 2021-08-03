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

namespace MultiSafepay\Test\Integration\Util;

use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\JsonHandler;

class JsonHandlerTest extends AbstractTestCase
{
    /**
     * @var JsonHandler
     */
    private $jsonHandler;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->jsonHandler = $this->getObjectManager()->create(JsonHandler::class);
    }

    /**
     * @dataProvider customJsonArrayDataProvider
     *
     * @param string $testJson
     * @param array $testArray
     */
    public function testConvertToJson(string $testJson, array $testArray): void
    {
        self::assertEquals($testJson, $this->jsonHandler->convertToJSON($testArray));
    }

    /**
     * @dataProvider customJsonArrayDataProvider
     *
     * @param string $testJson
     * @param array $testArray
     */
    public function testReadJson(string $testJson, array $testArray): void
    {
        self::assertEquals($testArray, $this->jsonHandler->readJSON($testJson));
        self::assertEquals([], $this->jsonHandler->readJSON('{"test1":{"test1_1":'));
    }

    /**
     * @dataProvider customJsonArrayDataProvider
     *
     * @param string $testJson
     * @param array $testArray
     * @param string $testPrettyJson
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function testConvertToPrettyJson(string $testJson, array $testArray, string $testPrettyJson): void
    {
        self::assertEquals($testPrettyJson, $this->jsonHandler->convertToPrettyJSON($testArray));
    }

    /**
     * @return array
     */
    public function customJsonArrayDataProvider(): array
    {
        return [
            [
                'json' => '{"test1":{"test1_1":"test_value_1","test1_2":"test_value_2"},"test2":"test_value_3"}',
                'array' => [
                    'test1' => [
                        'test1_1' => 'test_value_1',
                        'test1_2' => 'test_value_2',
                    ],
                    'test2' => 'test_value_3',
                ],
                'prettyJson' => '{
    "test1": {
        "test1_1": "test_value_1",
        "test1_2": "test_value_2"
    },
    "test2": "test_value_3"
}',
            ],
        ];
    }
}
