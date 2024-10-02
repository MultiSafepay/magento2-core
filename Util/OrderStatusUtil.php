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

    /**
     * OrderStatusUtil constructor.
     *
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @param Order $order
     * @return string
     */
    public function getPendingStatus(Order $order): string
    {
        if ($status = $this->config->getPendingStatus($order->getStoreId())) {
            return $status;
        }

        return $order->getConfig()->getStateDefaultStatus(Order::STATE_NEW) ?? ORDER::STATE_NEW;
    }

    /**
     * @param Order $order
     * @return string
     */
    public function getPendingPaymentStatus(Order $order): string
    {
        if ($status = $this->config->getPendingPaymentStatus($order->getStoreId())) {
            return $status;
        }

        return $order->getConfig()->getStateDefaultStatus(Order::STATE_PENDING_PAYMENT) ?? ORDER::STATE_PENDING_PAYMENT;
    }

    /**
     * @param Order $order
     * @return string
     */
    public function getProcessingStatus(Order $order): string
    {
        if ($orderStatus = $this->config->getProcessingStatus($order->getStoreId())) {
            return $orderStatus;
        }

        return $order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING) ?? Order::STATE_PROCESSING;
    }

    /**
     * @param OrderInterface $order
     * @param string $transactionStatus
     * @return string
     */
    public function getOrderStatusByTransactionStatus(OrderInterface $order, string $transactionStatus): string
    {
        return $this->config->getStatusByTransactionStatus($transactionStatus, $order->getStoreId());
    }
}
