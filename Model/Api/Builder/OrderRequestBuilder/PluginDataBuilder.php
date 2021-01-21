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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder;

use Magento\Framework\App\ProductMetadataInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PluginDetails;
use MultiSafepay\ConnectCore\Util\VersionUtil;

class PluginDataBuilder
{
    /**
     * @var ProductMetadataInterface
     */
    private $metadata;

    /**
     * @var PluginDetails
     */
    private $pluginDetails;

    /**
     * @var VersionUtil
     */
    private $versionUtil;

    /**
     * PluginDetails constructor.
     *
     * @param ProductMetadataInterface $metadata
     * @param PluginDetails $pluginDetails
     * @param VersionUtil $versionUtil
     */
    public function __construct(
        ProductMetadataInterface $metadata,
        PluginDetails $pluginDetails,
        VersionUtil $versionUtil
    ) {
        $this->metadata = $metadata;
        $this->pluginDetails = $pluginDetails;
        $this->versionUtil = $versionUtil;
    }

    /**
     * @return PluginDetails
     */
    public function build(): PluginDetails
    {
        return $this->pluginDetails->addApplicationName($this->metadata->getName())
            ->addApplicationVersion($this->metadata->getVersion())
            ->addPluginVersion($this->versionUtil->getPluginVersion());
    }
}
