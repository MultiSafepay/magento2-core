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
    private const THIRD_PARTY_CHECKOUTS = [
        [
            'name' => 'Hyva React Checkout',
            'config_path' => 'hyva_react_checkout/general/enable',
            'composer_package' => 'hyva-themes/magento2-react-checkout',
            'multisafepay_composer_package' => null,
        ],
        [
            'name' => 'Loki Checkout',
            'config_path' => 'loki_checkout/general/enable',
            'composer_package' => 'loki-checkout/magento2-core',
            'multisafepay_composer_package' => 'loki-checkout/magento2-multi-safepay'
        ],
    ];

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
    public function build(OrderInterface $order, OrderRequest $orderRequest): void
    {
        $storeId = (int)$order->getStoreId();

        foreach (self::THIRD_PARTY_CHECKOUTS as $checkout) {
            $this->addThirdPartyCheckoutData(
                $orderRequest,
                $storeId,
                $checkout['config_path'],
                $checkout['name'],
                $checkout['composer_package'],
                $checkout['multisafepay_composer_package']
            );
        }
    }

    /**
     * Add third party plugin information if it is enabled
     *
     * @param OrderRequest $orderRequest
     * @param int $storeId
     * @param string $configPath
     * @param string $checkoutName
     * @param string $composerPackage
     * @param string|null $compatibilityPackage
     * @return void
     */
    private function addThirdPartyCheckoutData(
        OrderRequest $orderRequest,
        int $storeId,
        string $configPath,
        string $checkoutName,
        string $composerPackage,
        ?string $compatibilityPackage = null
    ): void {
        if (!$this->isThirdPartyCheckoutEnabled($storeId, $configPath)) {
            return;
        }

        $pluginDetails = $orderRequest->getPluginDetails();

        // Application Name
        $applicationName = $pluginDetails->getApplicationName();
        $pluginDetails->addApplicationName($applicationName . ' - ' . $checkoutName);

        // MultiSafepay Plugin Version
        $pluginVersion = $pluginDetails->getPluginVersion()->getPluginVersion();

        // MultiSafepay Compatibility Plugin Version
        $thirdPartyMultiSafepayCheckoutVersion = 'unknown';
        if ($compatibilityPackage && method_exists('\Composer\InstalledVersions', 'getVersion')) {
            try {
                $thirdPartyMultiSafepayCheckoutVersion = \Composer\InstalledVersions::getVersion($compatibilityPackage);
            } catch (OutOfBoundsException $exception) {
                $this->logger->logExceptionForOrder($orderRequest->getOrderId(), $exception);
            }
        }
        $pluginDetails->addPluginVersion($pluginVersion . ' - ' . $thirdPartyMultiSafepayCheckoutVersion);

        // Third Party Checkout Version
        $thirdPartyCheckoutVersion = 'unknown';
        if (method_exists('\Composer\InstalledVersions', 'getVersion')) {
            try {
                $thirdPartyCheckoutVersion = \Composer\InstalledVersions::getVersion($composerPackage);
            } catch (OutOfBoundsException $exception) {
                $this->logger->logExceptionForOrder($orderRequest->getOrderId(), $exception);
            }
        }
        $applicationVersion = $pluginDetails->getApplicationVersion();
        $pluginDetails->addApplicationVersion($applicationVersion . ' - ' . $thirdPartyCheckoutVersion);
    }

    /**
     * Check if a third party plugin is enabled
     *
     * @param int $storeId
     * @param string $configPath
     * @return bool
     */
    private function isThirdPartyCheckoutEnabled(int $storeId, string $configPath): bool
    {
        return (bool)$this->scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
