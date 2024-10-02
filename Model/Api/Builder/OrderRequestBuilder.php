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

namespace MultiSafepay\ConnectCore\Model\Api\Builder;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Config\Config;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\ConnectCore\Util\CurrencyUtil;
use MultiSafepay\ConnectCore\Util\PriceUtil;
use MultiSafepay\ValueObject\Money;

class OrderRequestBuilder
{
    /**
     * @var OrderRequest
     */
    private $orderRequest;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CurrencyUtil
     */
    private $currencyUtil;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var PriceUtil
     */
    private $priceUtil;

    /**
     * @var OrderRequestBuilderPool
     */
    private $orderRequestBuilderPool;

    /**
     * OrderRequestBuilder constructor.
     *
     * @param Config $config
     * @param ManagerInterface $eventManager
     * @param PriceUtil $priceUtil
     * @param OrderRequest $orderRequest
     * @param CurrencyUtil $currencyUtil
     * @param OrderRequestBuilderPool $orderRequestBuilderPool
     */
    public function __construct(
        Config $config,
        ManagerInterface $eventManager,
        PriceUtil $priceUtil,
        OrderRequest $orderRequest,
        CurrencyUtil $currencyUtil,
        OrderRequestBuilderPool $orderRequestBuilderPool
    ) {
        $this->config = $config;
        $this->eventManager = $eventManager;
        $this->orderRequest = $orderRequest;
        $this->currencyUtil = $currencyUtil;
        $this->priceUtil = $priceUtil;
        $this->orderRequestBuilderPool = $orderRequestBuilderPool;
    }

    /**
     * @param Order $order
     * @return OrderRequest
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function build(Order $order): OrderRequest
    {
        $payment = $order->getPayment();
        $orderId = (string) $order->getRealOrderId();

        if (!$payment instanceof OrderPaymentInterface) {
            throw new LocalizedException(
                __('The transaction could not be created, because the payment object is missing from the order')
            );
        }

        $this->config->setMethodCode($payment->getMethod());
        $currencyCode = $this->currencyUtil->getCurrencyCode($order);
        $grandTotal = $this->priceUtil->getGrandTotal($order);
        $orderRequest = $this->orderRequest->addOrderId($orderId)
            ->addMoney(new Money($grandTotal * 100, $currencyCode))
            ->addGatewayCode((string) $this->config->getValue('gateway_code'));

        foreach ($this->orderRequestBuilderPool->getOrderRequestBuilders() as $orderRequestBuilder) {
            $orderRequestBuilder->build($order, $payment, $orderRequest);
        }

        $this->eventManager->dispatch(
            'before_send_multisafepay_order_request',
            ['order' => $order, 'orderRequest' => $orderRequest]
        );

        return $orderRequest;
    }
}
