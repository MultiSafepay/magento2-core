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

namespace MultiSafepay\ConnectCore\Service;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\ConnectCore\Model\Api\Initializer\OrderRequestInitializer;
use Psr\Http\Client\ClientExceptionInterface;

class PaymentLink
{
    /**
     * @var OrderRequestInitializer
     */
    private $orderRequestInitializer;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var State
     */
    private $state;

    /**
     * PaymentLink constructor.
     *
     * @param OrderRequestInitializer $orderRequestInitializer
     * @param OrderRepositoryInterface $orderRepository
     * @param State $state
     */
    public function __construct(
        OrderRequestInitializer $orderRequestInitializer,
        OrderRepositoryInterface $orderRepository,
        State $state
    ) {
        $this->orderRequestInitializer = $orderRequestInitializer;
        $this->orderRepository = $orderRepository;
        $this->state = $state;
    }

    /**
     * @param OrderInterface $order
     * @return string
     * @throws ClientExceptionInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getPaymentLinkByOrder(OrderInterface $order): string
    {
        $transaction = $this->orderRequestInitializer->initialize($order);

        return $transaction->getPaymentUrl();
    }

    /**
     * @param OrderInterface $order
     * @param string $paymentLink
     * @return void
     * @throws LocalizedException
     */
    public function addPaymentLink(OrderInterface $order, string $paymentLink): void
    {
        $this->addToAdditionalInformation($order->getPayment(), $paymentLink);
        $this->addPaymentLinkToOrderComments($order, $paymentLink);
    }

    /**
     * @param Payment $payment
     * @param string $paymentUrl
     * @return void
     * @throws LocalizedException
     */
    private function addToAdditionalInformation(Payment $payment, string $paymentUrl): void
    {
        if ($payment !== null) {
            $payment->setAdditionalInformation('payment_link', $paymentUrl);
        }
    }

    /**
     * @param OrderInterface $order
     * @param string $paymentUrl
     * @return void
     */
    private function addPaymentLinkToOrderComments(OrderInterface $order, string $paymentUrl): void
    {
        $order->addCommentToStatusHistory($this->getOrderCommentByAreaCode($paymentUrl));
        $this->orderRepository->save($order);
    }

    /**
     * @param string $paymentUrl
     * @return Phrase
     */
    private function getOrderCommentByAreaCode(string $paymentUrl): Phrase
    {
        try {
            $areaCode = $this->state->getAreaCode();
        } catch (LocalizedException $localizedException) {
            $areaCode = Area::AREA_ADMINHTML;
        }

        return $areaCode === Area::AREA_ADMINHTML
            ? __('Payment link for this transaction: %1', $paymentUrl)
            : __('The user has been redirected to the following page: %1', $paymentUrl);
    }
}
