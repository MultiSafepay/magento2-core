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

namespace MultiSafepay\Test\Integration\Util;

use Exception;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\EncryptorUtil;

class EncryptorUtilTest extends AbstractTestCase
{
    /**
     * @var EncryptorUtil
     */
    private $encryptorUtil;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->encryptorUtil = $this->getObjectManager()->create(EncryptorUtil::class);
    }

    /**
     * @throws Exception
     */
    public function testDecryptWithUnhashedValue(): void
    {
        self::assertSame('test', $this->encryptorUtil->decrypt('test'));
    }
}
