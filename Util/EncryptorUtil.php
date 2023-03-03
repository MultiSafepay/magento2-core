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

namespace MultiSafepay\ConnectCore\Util;

use Exception;
use Magento\Framework\Encryption\Encryptor;

class EncryptorUtil
{
    /**
     * @var Encryptor
     */
    private $encryptor;

    /**
     * EncryptorUtil constructor.
     *
     * @param Encryptor $encryptor
     */
    public function __construct(
        Encryptor $encryptor
    ) {
        $this->encryptor = $encryptor;
    }

    /**
     * @throws Exception
     */
    public function decrypt(string $key): string
    {
        return $this->encryptor->validateHashVersion($key) ? $key : $this->encryptor->decrypt($key);
    }
}
