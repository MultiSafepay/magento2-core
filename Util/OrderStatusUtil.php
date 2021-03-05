<?php

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Util;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Config\Config;

class OrderStatusUtil
{
    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @param OrderInterface $order
     * @return string
     */
    public function getPendingPaymentStatus(OrderInterface $order): string
    {
        if ($status = $this->config->getPendingPaymentStatus($order->getStoreId())) {
            return $status;
        }

        return $order->getConfig()->getStateDefaultStatus(Order::STATE_PENDING_PAYMENT) ?? ORDER::STATE_PENDING_PAYMENT;
    }
}
