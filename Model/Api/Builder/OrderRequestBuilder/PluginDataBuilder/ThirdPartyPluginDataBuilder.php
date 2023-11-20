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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\PluginDataBuilder;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\ScopeInterface;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\ConnectCore\Logger\Logger;
use OutOfBoundsException;

class ThirdPartyPluginDataBuilder
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * ThirdPartyPluginDataBuilder constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Logger $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * @param OrderInterface $order
     * @param OrderRequest $orderRequest
     * @return void
     */
    public function build(OrderInterface $order, OrderRequest $orderRequest)
    {
        $storeId = (int)$order->getStoreId();

        $this->addReactCheckoutDataIfEnabled($orderRequest, $storeId);
    }

    /**
     * Add React Checkout plugin information if it enabled
     *
     * @param OrderRequest $orderRequest
     * @param int $storeId
     * @return void
     */
    private function addReactCheckoutDataIfEnabled(OrderRequest $orderRequest, int $storeId): void
    {
        if ($this->reactCheckoutEnabled($storeId)) {
            $pluginDetails = $orderRequest->getPluginDetails();

            $applicationName = $pluginDetails->getApplicationName();
            $pluginDetails->addApplicationName($applicationName . ' - Hyva React Checkout');

            $pluginVersion = $pluginDetails->getPluginVersion()->getPluginVersion();
            $pluginDetails->addPluginVersion($pluginVersion . ' - ' . 'unknown');

            $reactCheckoutVersion = 'unknown';

            if (method_exists('\Composer\InstalledVersions', 'getVersion')) {
                try {
                    $reactCheckoutVersion = \Composer\InstalledVersions::getVersion(
                        'hyva-themes/magento2-react-checkout'
                    );
                } catch (OutOfBoundsException $exception) {
                    $this->logger->logExceptionForOrder($orderRequest->getOrderId(), $exception);
                }
            }

            $applicationVersion = $pluginDetails->getApplicationVersion();
            $pluginDetails->addApplicationVersion($applicationVersion . ' - ' . $reactCheckoutVersion);
        }
    }

    /**
     * Check if the Hyva React Checkout is enabled
     *
     * @param int $storeId
     * @return bool
     */
    private function reactCheckoutEnabled(int $storeId): bool
    {
        return (bool)$this->scopeConfig->getValue(
            'hyva_react_checkout/general/enable',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
