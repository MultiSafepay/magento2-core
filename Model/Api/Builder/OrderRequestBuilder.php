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

namespace MultiSafepay\ConnectCore\Model\Api\Builder;

use Exception;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Config\Config;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\CustomerBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\DeliveryBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\DescriptionBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\GatewayInfoBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\PaymentOptionsBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\PluginDataBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\SecondsActiveBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\ShoppingCartBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\TransactionTypeBuilder;
use MultiSafepay\ConnectCore\Util\CurrencyUtil;
use MultiSafepay\ValueObject\Money;

class OrderRequestBuilder
{

    /**
     * @var CustomerBuilder
     */
    private $customerBuilder;

    /**
     * @var GatewayInfoBuilder
     */
    private $gatewayInfoBuilder;

    /**
     * @var PaymentOptionsBuilder
     */
    private $paymentOptionsBuilder;

    /**
     * @var PluginDataBuilder
     */
    private $pluginDataBuilder;

    /**
     * @var OrderRequest
     */
    private $orderRequest;

    /**
     * @var ShoppingCartBuilder
     */
    private $shoppingCartBuilder;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SecondsActiveBuilder
     */
    private $secondsActiveBuilder;

    /**
     * @var CurrencyUtil
     */
    private $currencyUtil;

    /**
     * @var TransactionTypeBuilder
     */
    private $transactionTypeBuilder;

    /**
     * @var DeliveryBuilder
     */
    private $deliveryBuilder;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var DescriptionBuilder
     */
    private $descriptionBuilder;

    /**
     * Data constructor.
     *
     * @param CustomerBuilder $customerBuilder
     * @param Config $config
     * @param DescriptionBuilder $descriptionBuilder
     * @param DeliveryBuilder $deliveryBuilder
     * @param ManagerInterface $eventManager
     * @param GatewayInfoBuilder $gatewayInfoBuilder
     * @param ShoppingCartBuilder $shoppingCartBuilder
     * @param PaymentOptionsBuilder $paymentOptionsBuilder
     * @param PluginDataBuilder $pluginDataBuilder
     * @param SecondsActiveBuilder $secondsActiveBuilder
     * @param OrderRequest $orderRequest
     * @param CurrencyUtil $currencyUtil
     * @param TransactionTypeBuilder $transactionTypeBuilder
     */
    public function __construct(
        CustomerBuilder $customerBuilder,
        Config $config,
        DescriptionBuilder $descriptionBuilder,
        DeliveryBuilder $deliveryBuilder,
        ManagerInterface $eventManager,
        GatewayInfoBuilder $gatewayInfoBuilder,
        ShoppingCartBuilder $shoppingCartBuilder,
        PaymentOptionsBuilder $paymentOptionsBuilder,
        PluginDataBuilder $pluginDataBuilder,
        SecondsActiveBuilder $secondsActiveBuilder,
        OrderRequest $orderRequest,
        CurrencyUtil $currencyUtil,
        TransactionTypeBuilder $transactionTypeBuilder
    ) {
        $this->customerBuilder = $customerBuilder;
        $this->config = $config;
        $this->deliveryBuilder = $deliveryBuilder;
        $this->eventManager = $eventManager;
        $this->gatewayInfoBuilder = $gatewayInfoBuilder;
        $this->shoppingCartBuilder = $shoppingCartBuilder;
        $this->paymentOptionsBuilder = $paymentOptionsBuilder;
        $this->pluginDataBuilder = $pluginDataBuilder;
        $this->secondsActiveBuilder = $secondsActiveBuilder;
        $this->orderRequest = $orderRequest;
        $this->currencyUtil = $currencyUtil;
        $this->transactionTypeBuilder = $transactionTypeBuilder;
        $this->descriptionBuilder = $descriptionBuilder;
    }

    /**
     * @param OrderInterface $order
     * @return OrderRequest
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function build(OrderInterface $order): OrderRequest
    {
        $payment = $order->getPayment();
        $orderId = (string) $order->getRealOrderId();

        if (!$payment instanceof OrderPaymentInterface) {
            throw new LocalizedException(
                __('The transaction could not be created, because the payment object is missing from the order')
            );
        }

        $this->config->setMethodCode($payment->getMethod());

        $currencyCode = $this->currencyUtil->getCurrencyCodeByOrder($order);
        $type = $this->transactionTypeBuilder->build($payment, $this->config);

        $orderRequest = $this->orderRequest->addType($type)
            ->addOrderId($orderId)
            ->addMoney(new Money((float) $order->getBaseGrandTotal() * 100, $currencyCode))
            ->addDescription($this->descriptionBuilder->build($orderId))
            ->addGatewayCode((string) $this->config->getValue('gateway_code'))
            ->addPaymentOptions($this->paymentOptionsBuilder->build($orderId))
            ->addCustomer($this->customerBuilder->build($order))
            ->addShoppingCart($this->shoppingCartBuilder->build($order))
            ->addPluginDetails($this->pluginDataBuilder->build());

        $this->gatewayInfoBuilder->build($order, $payment, $orderRequest);
        $this->secondsActiveBuilder->build($orderRequest, $this->config);
        $this->deliveryBuilder->build($order, $payment, $orderRequest);

        $this->eventManager->dispatch(
            'before_send_multisafepay_order_request',
            ['order' => $order, 'orderRequest' => $orderRequest]
        );

        return $orderRequest;
    }
}
