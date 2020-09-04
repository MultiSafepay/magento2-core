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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder;

use Magento\Framework\App\ProductMetadataInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PluginDetails;

class PluginDataBuilder
{
    public const VERSION = '2.0.0';
    /**
     * @var ProductMetadataInterface
     */
    private $metadata;
    /**
     * @var PluginDetails
     */
    private $pluginDetails;

    /**
     * PluginDetails constructor.
     *
     * @param ProductMetadataInterface $metadata
     * @param PluginDetails $pluginDetails
     */
    public function __construct(
        ProductMetadataInterface $metadata,
        PluginDetails $pluginDetails
    ) {
        $this->metadata = $metadata;
        $this->pluginDetails = $pluginDetails;
    }

    /**
     * @return PluginDetails
     */
    public function build(): PluginDetails
    {
        return $this->pluginDetails->addApplicationName($this->metadata->getName())
            ->addApplicationVersion($this->metadata->getVersion())
            ->addPluginVersion(self::VERSION);
    }
}
