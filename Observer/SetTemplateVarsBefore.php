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
 * Copyright © 2022 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Observer;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Service\PaymentLink;

class SetTemplateVarsBefore implements ObserverInterface
{
    /**
     * @var State
     */
    private $state;

    /**
     * @var PaymentLink
     */
    private $paymentLink;

    /**
     * SetTemplateVarsBefore constructor.
     *
     * @param State $state
     * @param PaymentLink $paymentLink
     */
    public function __construct(
        State $state,
        PaymentLink $paymentLink
    ) {
        $this->state = $state;
        $this->paymentLink = $paymentLink;
    }

    /**
     * @param Observer $observer
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        if ($this->state->getAreaCode() !== Area::AREA_ADMINHTML) {
            return;
        }

        $transport = $this->getTransportObject($observer);
        /** @var Order $order */
        $order = $transport->getOrder();

        if ($order instanceof OrderInterface && ($paymentUrl = $this->paymentLink->getPaymentLinkFromOrder($order))) {
            $transport['payment_link'] = $paymentUrl;
        }
    }

    /**
     * Event argument `transport` is deprecated since Magento 2.2.6. Using `transportObject` instead if exists.
     *
     * @param Observer $observer
     * @return mixed
     */
    private function getTransportObject(Observer $observer)
    {
        if (array_key_exists('transportObject', $observer->getData())) {
            return $observer->getData()['transportObject'];
        }

        return $observer->getTransport();
    }
}
