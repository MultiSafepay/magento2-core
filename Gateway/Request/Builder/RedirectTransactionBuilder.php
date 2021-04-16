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

namespace MultiSafepay\ConnectCore\Gateway\Request\Builder;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Service\EmailSender;
use MultiSafepay\ConnectCore\Util\OrderStatusUtil;

class RedirectTransactionBuilder implements BuilderInterface
{
    /**
     * @var OrderStatusUtil
     */
    private $orderStatusUtil;

    /**
     * @var State
     */
    private $state;

    /**
     * @var EmailSender
     */
    private $emailSender;

    /**
     * RedirectTransactionBuilder constructor.
     *
     * @param EmailSender $emailSender
     * @param OrderStatusUtil $orderStatusUtil
     * @param State $state
     */
    public function __construct(
        EmailSender $emailSender,
        OrderStatusUtil $orderStatusUtil,
        State $state
    ) {
        $this->state = $state;
        $this->emailSender = $emailSender;
        $this->orderStatusUtil = $orderStatusUtil;
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     */
    public function build(array $buildSubject): array
    {
        $stateObject = $buildSubject['stateObject'];

        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();
        $order = $payment->getOrder();

        $state = Order::STATE_NEW;
        $orderStatus = $this->orderStatusUtil->getPendingStatus($order);

        $stateObject->setState($state);
        $stateObject->setStatus($orderStatus);

        // Early return on backend order
        if ($this->state->getAreaCode() === Area::AREA_ADMINHTML) {
            return [];
        }

        // If not backend order, check when order confirmation e-mail needs to be sent
        if (!$this->emailSender->checkOrderConfirmationBeforeTransaction()) {
            $stateObject->setIsNotified(false);
            $order->setCanSendNewEmailFlag(false);
        }

        return [];
    }
}
