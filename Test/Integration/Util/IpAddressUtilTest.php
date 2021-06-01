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
use MultiSafepay\ConnectCore\Util\IpAddressUtil;

class IpAddressUtilTest extends AbstractTestCase
{
    /**
     * @var IpAddressUtil
     */
    private $ipAddressUtil;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->ipAddressUtil = $this->getObjectManager()->create(IpAddressUtil::class);
    }

    public function testGetAmountWithBaseCurrencySetting(): void
    {
        $ip = '127.0.0.1';

        self::assertEquals($ip, $this->ipAddressUtil->validateIpAddress($ip));
        self::assertNotEquals($ip, $this->ipAddressUtil->validateIpAddress($ip . ':2020, 123123'));
    }
}
