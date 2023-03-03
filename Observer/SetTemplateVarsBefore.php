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

namespace MultiSafepay\ConnectCore\Observer;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
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
     * Retrieve the payment link from the order and add it to the Transport object
     *
     * @param Observer $observer
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        if ($this->state->getAreaCode() !== Area::AREA_ADMINHTML) {
            return;
        }

        $transport = $this->getTransportObject($observer);
        $order = $this->getOrderFromTransportObject($transport);

        if ($order === null) {
            return;
        }

        if ($paymentUrl = $this->paymentLink->getPaymentLinkFromOrder($order)) {
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

    /**
     * Retrieve the order from the Transport object
     *
     * phpcs:ignore
     * @param $transport
     * @return OrderInterface|null
     */
    private function getOrderFromTransportObject($transport): ?OrderInterface
    {
        if (is_array($transport) && array_key_exists('order', $transport)) {
            return $transport['order'];
        }

        if (is_object($transport) && $transport->getOrder() instanceof OrderInterface) {
            return $transport->getOrder();
        }

        return null;
    }
}
