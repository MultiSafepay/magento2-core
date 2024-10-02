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

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\Api\Transactions\Gateways;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Util\CurrencyUtil;
use MultiSafepay\Exception\InvalidArgumentException;

class ShoppingCartBuilder implements OrderRequestBuilderInterface
{
    /**
     * @var array
     */
    protected $shoppingCartBuilders;

    /**
     * @var CurrencyUtil
     */
    private $currencyUtil;

    /**
     * @var Config
     */
    private $config;

    /**
     * ShoppingCartBuilder constructor.
     *
     * @param CurrencyUtil $currencyUtil
     * @param Config $config
     * @param array $shoppingCartBuilders
     */
    public function __construct(
        CurrencyUtil $currencyUtil,
        Config $config,
        array $shoppingCartBuilders
    ) {
        $this->currencyUtil = $currencyUtil;
        $this->config = $config;
        $this->shoppingCartBuilders = $shoppingCartBuilders;
    }

    /**
     * @param Order $order
     * @param Payment $payment
     * @param OrderRequest $orderRequest
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InvalidArgumentException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function build(Order $order, Payment $payment, OrderRequest $orderRequest): void
    {
        if (!$this->isShoppingCartNeeded($orderRequest)) {
            return;
        }

        $items = [];
        $currency = $this->currencyUtil->getCurrencyCode($order);

        foreach ($this->shoppingCartBuilders as $shoppingCartBuilder) {
            $items[] = $shoppingCartBuilder->build($order, $currency);
        }

        $orderRequest->addShoppingCart(new ShoppingCart(array_merge([], ...$items)));
    }

    /**
     * @param OrderRequest $orderRequest
     * @return bool
     */
    private function isShoppingCartNeeded(OrderRequest $orderRequest): bool
    {
        return !($this->config->getAdvancedValue(Config::ADVANCED_DISABLE_SHOPPING_CART)
                 && !in_array($orderRequest->getGatewayCode(), Gateways::SHOPPING_CART_REQUIRED_GATEWAYS));
    }
}
