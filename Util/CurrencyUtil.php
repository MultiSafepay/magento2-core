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

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use MultiSafepay\ConnectCore\Config\Config;

class CurrencyUtil
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Config
     */
    private $config;

    /**
     * Currency constructor.
     *
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Config $config,
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
        $this->config = $config;
    }

    /**
     * @param OrderInterface $order
     * @return string
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getCurrencyCode(OrderInterface $order): string
    {
        if ($this->config->useBaseCurrency($order->getStoreId())) {
            return $this->getBaseCurrencyCode((string)$order->getBaseCurrencyCode());
        }

        return $this->getOrderCurrencyCode($order);
    }

    /**
     * Check if the Base Currency Code can be retrieved from the order, if not then retrieve it from the Store Manager
     *
     * @param string $orderBaseCurrencyCode
     * @return string
     * @throws NoSuchEntityException
     */
    public function getBaseCurrencyCode(string $orderBaseCurrencyCode = ''): string
    {
        if (!empty($orderBaseCurrencyCode)) {
            return (string)$orderBaseCurrencyCode;
        }

        /** @var Store $store */
        $store = $this->storeManager->getStore();

        return (string)$store->getBaseCurrencyCode();
    }

    /**
     * Try to retrieve the Currency Code via the order or else retrieve the current currency via the Store Manager
     *
     * @param OrderInterface $order
     * @return string
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getOrderCurrencyCode(OrderInterface $order): string
    {
        $orderCurrencyCode = $order->getOrderCurrencyCode();
        if (!empty($orderCurrencyCode)) {
            return (string)$orderCurrencyCode;
        }

        /** @var Store $store */
        $store =  $this->storeManager->getStore($order->getStoreId());

        $orderCurrencyCode = $store->getCurrentCurrency()->getCode();

        if (!empty($orderCurrencyCode)) {
            return (string)$orderCurrencyCode;
        }

        throw new LocalizedException(__('No currency code set'));
    }
}
