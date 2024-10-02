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

namespace MultiSafepay\ConnectCore\Model\Ui;

class ConfigProviderPool
{
    /**
     * @var GenericConfigProvider[]
     */
    private $configProviders;

    /**
     * Pool constructor.
     *
     * @param GenericConfigProvider[] $configProviders
     */
    public function __construct(array $configProviders)
    {
        $this->configProviders = $configProviders;
    }

    /**
     * @param string $code
     * @return GenericConfigProvider|null
     */
    public function getConfigProviderByCode(string $code): ?GenericConfigProvider
    {
        return $this->configProviders[$code] ?? null;
    }

    /**
     * @return GenericConfigProvider[]
     */
    public function getConfigProviders(): array
    {
        return $this->configProviders;
    }
}
