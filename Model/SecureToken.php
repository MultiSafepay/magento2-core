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

namespace MultiSafepay\ConnectCore\Model;

use Exception;
use InvalidArgumentException;
use Magento\Framework\Encryption\Encryptor;
use MultiSafepay\ConnectCore\Config\Config;

class SecureToken
{
    /**
     * @var Encryptor
     */
    private $encryptor;

    /**
     * @var Config
     */
    private $config;

    /**
     * SecureToken constructor.
     *
     * @param Config $config
     * @param Encryptor $encryptor
     */
    public function __construct(
        Config $config,
        Encryptor $encryptor
    ) {
        $this->config = $config;
        $this->encryptor = $encryptor;
    }

    /**
     * @param string $originalValue
     * @return string
     * @throws Exception
     */
    public function generate(string $originalValue): string
    {
        $apiKey = $this->config->getApiKey();

        if (empty($apiKey)) {
            throw new InvalidArgumentException('No API key configured');
        }

        if (empty($originalValue)) {
            throw new InvalidArgumentException('Original value param can\'t be empty');
        }

        $hash = $this->encryptor->getHash($apiKey, $originalValue);
        $secureToken = explode(':', $hash);

        return (string) $secureToken[0];
    }

    /**
     * @param string $originalValue
     * @param string $secureToken
     * @return bool
     * @throws Exception
     */
    public function validate(string $originalValue, string $secureToken): bool
    {
        return hash_equals($this->generate($originalValue), $secureToken);
    }
}
