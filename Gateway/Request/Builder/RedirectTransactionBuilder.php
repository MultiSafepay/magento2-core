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
use Magento\Sales\Model\Order\StatusResolver;
use MultiSafepay\ConnectCore\Service\EmailSender;

class RedirectTransactionBuilder implements BuilderInterface
{

    /**
     * @var StatusResolver
     */
    private $statusResolver;

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
     * @param State $state
     * @param StatusResolver $statusResolver
     */
    public function __construct(
        EmailSender $emailSender,
        State $state,
        StatusResolver $statusResolver
    ) {
        $this->statusResolver = $statusResolver;
        $this->state = $state;
        $this->emailSender = $emailSender;
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

        $state = Order::STATE_NEW;
        $orderStatus = $this->statusResolver->getOrderStatusByState($payment->getOrder(), $state);

        $stateObject->setState($state);
        $stateObject->setStatus($orderStatus);

        // Early return on backend order
        if ($this->state->getAreaCode() === Area::AREA_ADMINHTML) {
            return [];
        }

        // If not backend order, check when order confirmation e-mail needs to be sent
        if (!$this->emailSender->checkOrderConfirmationBeforeTransaction()) {
            $stateObject->setIsNotified(false);

            $order = $payment->getOrder();
            $order->setCanSendNewEmailFlag(false);
        }

        return [];
    }
}
