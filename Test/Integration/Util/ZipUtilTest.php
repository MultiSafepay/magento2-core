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

use Exception;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\ZipUtil;

class ZipUtilTest extends AbstractTestCase
{
    /**
     * @var ZipUtil
     */
    private $zipUtil;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->zipUtil = $this->getObjectManager()->create(ZipUtil::class);
    }

    public function testZipLogFiles(): void
    {
        self::expectException(Exception::class);
        self::expectExceptionMessage('File not found');

        $this->zipUtil->zipLogFiles();
    }
}
