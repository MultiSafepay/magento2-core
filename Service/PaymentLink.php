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

namespace MultiSafepay\ConnectCore\Service;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use MultiSafepay\ConnectCore\Model\Api\Initializer\OrderRequestInitializer;
use MultiSafepay\Exception\ApiException;
use Psr\Http\Client\ClientExceptionInterface;
use MultiSafepay\ConnectCore\Logger\Logger;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PaymentLink
{
    public const MULTISAFEPAY_PAYMENT_LINK_PARAM_NAME = 'payment_link';

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
     * @var Logger
     */
    private $logger;

    /**
     * PaymentLink constructor.
     *
     * @param OrderRequestInitializer $orderRequestInitializer
     * @param OrderRepositoryInterface $orderRepository
     * @param State $state
     * @param Logger $logger
     */
    public function __construct(
        OrderRequestInitializer $orderRequestInitializer,
        OrderRepositoryInterface $orderRepository,
        State $state,
        Logger $logger
    ) {
        $this->orderRequestInitializer = $orderRequestInitializer;
        $this->orderRepository = $orderRepository;
        $this->state = $state;
        $this->logger = $logger;
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

        if (!($paymentUrl = $transaction->getPaymentUrl())) {
            throw new ApiException('Payment url wasn\'t retrieved. Please try again.');
        }

        return $paymentUrl;
    }

    /**
     * Add the payment link to the order
     *
     * @param OrderInterface $order
     * @param string $paymentLink
     * @return void
     * @throws LocalizedException
     * @throws Exception
     */
    public function addPaymentLink(OrderInterface $order, string $paymentLink): void
    {
        $this->addToAdditionalInformation($order->getPayment(), $paymentLink);
        $this->addPaymentLinkToOrderComments($order, $paymentLink);
    }

    /**
     * @param OrderInterface $order
     * @return string
     */
    public function getPaymentLinkFromOrder(OrderInterface $order): string
    {
        if (!($paymentLink =
            (string)$order->getPayment()->getAdditionalInformation(self::MULTISAFEPAY_PAYMENT_LINK_PARAM_NAME)
        )) {
            return $order->getPayment()->getAdditionalInformation(Transaction::RAW_DETAILS)['payment_link'] ?? '';
        }

        return $paymentLink;
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
            $payment->setAdditionalInformation(
                self::MULTISAFEPAY_PAYMENT_LINK_PARAM_NAME,
                $paymentUrl
            );
        }
    }

    /**
     * Add the Payment link to the order comments if the order was placed in the admin backend
     *
     * @param OrderInterface $order
     * @param string $paymentUrl
     * @return void
     * @throws Exception
     */
    private function addPaymentLinkToOrderComments(OrderInterface $order, string $paymentUrl): void
    {
        if ($this->isAreaCodeAdminHtml()) {
            $order->addCommentToStatusHistory(__('Payment link for this transaction: %1', $paymentUrl)->render());
            $this->orderRepository->save($order);
        }
    }

    /**
     * Check if this is being executed from the backend
     *
     * @return bool
     */
    private function isAreaCodeAdminHtml(): bool
    {
        try {
            $areaCode = $this->state->getAreaCode();
        } catch (LocalizedException $localizedException) {
            $this->logger->logException($localizedException);
            return false;
        }

        return $areaCode === Area::AREA_ADMINHTML;
    }
}
