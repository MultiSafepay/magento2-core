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

namespace MultiSafepay\ConnectCore\Model;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\StatusResolver;
use MultiSafepay\ConnectCore\Api\StockReducerInterface;

class SecondChance
{
    /**
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var StockReducerInterface
     */
    private $stockReducer;

    /**
     * @var StatusResolver
     */
    private $statusResolver;

    /**
     * SecondChance constructor.
     *
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param StatusResolver $statusResolver
     * @param StockReducerInterface $stockReducer
     */
    public function __construct(
        OrderItemRepositoryInterface $orderItemRepository,
        OrderRepositoryInterface $orderRepository,
        StatusResolver $statusResolver,
        StockReducerInterface $stockReducer
    ) {
        $this->orderItemRepository = $orderItemRepository;
        $this->orderRepository = $orderRepository;
        $this->statusResolver = $statusResolver;
        $this->stockReducer = $stockReducer;
    }

    /**
     * @param OrderInterface $order
     */
    public function reopenOrder(OrderInterface $order): void
    {
        $order->setBaseDiscountCanceled(0)
                ->setBaseShippingCanceled(0)
                ->setBaseSubtotalCanceled(0)
                ->setBaseTaxCanceled(0)
                ->setBaseTotalCanceled(0)
                ->setDiscountCanceled(0)
                ->setShippingCanceled(0)
                ->setSubtotalCanceled(0)
                ->setTaxCanceled(0)
                ->setTotalCanceled(0);

        $this->stockReducer->reduce($order);

        foreach ($order->getAllItems() as $item) {
            if ($item->getQtyCanceled() > 0) {
                $item->setQtyCanceled(0);
                $this->orderItemRepository->save($item);
            }
        }

        $state = Order::STATE_NEW;
        $orderStatus = $this->statusResolver->getOrderStatusByState($order, $state);

        $order->setState($state)
                ->setStatus($orderStatus)
                ->addCommentToStatusHistory(
                    __('The order has been reopened because a new transaction was started by the customer'),
                    $orderStatus
                );
        $this->orderRepository->save($order);
    }
}
