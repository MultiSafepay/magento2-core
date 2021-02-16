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

namespace MultiSafepay\ConnectCore\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProviderPool
{
    /**
     * @var ConfigProviderInterface[]
     */
    private $configProviders;

    /**
     * Pool constructor.
     *
     * @param ConfigProviderInterface[] $configProviders
     */
    public function __construct(array $configProviders)
    {
        $this->configProviders = $configProviders;
    }

    /**
     * @param string $code
     * @return ConfigProviderInterface|null
     */
    public function getConfigProviderByCode(string $code): ?ConfigProviderInterface
    {
        return $this->configProviders[$code] ?? null;
    }

    /**
     * @return ConfigProviderInterface[]
     */
    public function getConfigProviders(): array
    {
        return $this->configProviders;
    }
}
