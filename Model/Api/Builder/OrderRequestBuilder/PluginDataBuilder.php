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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PluginDetails;
use MultiSafepay\ConnectCore\Model\Store;
use MultiSafepay\ConnectCore\Util\VersionUtil;

class PluginDataBuilder implements OrderRequestBuilderInterface
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
     * @var Store
     */
    private $store;

    /**
     * PluginDetails constructor.
     *
     * @param ProductMetadataInterface $metadata
     * @param PluginDetails $pluginDetails
     * @param VersionUtil $versionUtil
     * @param Store $store
     */
    public function __construct(
        ProductMetadataInterface $metadata,
        PluginDetails $pluginDetails,
        VersionUtil $versionUtil,
        Store $store
    ) {
        $this->metadata = $metadata;
        $this->pluginDetails = $pluginDetails;
        $this->versionUtil = $versionUtil;
        $this->store = $store;
    }

    /**
     * Add plugin details to the order request
     *
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param OrderRequest $orderRequest
     * @return void
     */
    public function build(
        OrderInterface $order,
        OrderPaymentInterface $payment,
        OrderRequest $orderRequest
    ): void {
        $orderRequest->addPluginDetails(
            $this->pluginDetails->addApplicationName(
                $this->metadata->getName() . ' ' . $this->metadata->getEdition()
            )
                ->addApplicationVersion($this->metadata->getVersion())
                ->addPluginVersion($this->versionUtil->getPluginVersion())
                ->addShopRootUrl($this->store->getBaseUrl() ?? 'unknown')
        );
    }
}
