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
 * Copyright © 2021 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Plugin\Sales\Model;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Service\OrderService;
use MultiSafepay\ConnectCore\Service\Order\CancelMultisafepayOrderPretransaction;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;

class OrderPlugin
{
    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var CancelMultisafepayOrderPretransaction
     */
    private $cancelMultisafepayOrderPretransaction;

    /**
     * OrderPlugin constructor.
     *
     * @param PaymentMethodUtil $paymentMethodUtil
     * @param OrderService $orderService
     * @param CancelMultisafepayOrderPretransaction $cancelMultisafepayOrderPretransaction
     */
    public function __construct(
        PaymentMethodUtil $paymentMethodUtil,
        OrderService $orderService,
        CancelMultisafepayOrderPretransaction $cancelMultisafepayOrderPretransaction
    ) {
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->orderService = $orderService;
        $this->cancelMultisafepayOrderPretransaction = $cancelMultisafepayOrderPretransaction;
    }

    /**
     * @param OrderInterface $subject
     * @return OrderInterface[]
     */
    public function beforeCancel(OrderInterface $subject): array
    {
        if ($subject->canCancel()
            && $this->paymentMethodUtil->isMultisafepayOrder($subject)
            && $subject->getState() === Order::STATE_PENDING_PAYMENT
        ) {
            $this->cancelMultisafepayOrderPretransaction->execute($subject);
        }

        return [$subject];
    }
}
