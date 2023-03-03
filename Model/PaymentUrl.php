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

namespace MultiSafepay\ConnectCore\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use MultiSafepay\ConnectCore\Api\PaymentUrlInterface;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\PaymentLink;

class PaymentUrl implements PaymentUrlInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var PaymentLink
     */
    private $paymentLink;

    /**
     * PaymentUrl constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param Logger $logger
     * @param PaymentLink $paymentLink
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Logger $logger,
        PaymentLink $paymentLink
    ) {
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->paymentLink = $paymentLink;
    }

    /**
     * Get the payment URL from an order
     *
     * @param int $orderId
     * @param int $customerId
     * @param int|null $cartId
     * @return string
     */
    public function getPaymentUrl(int $orderId, ?int $customerId, ?int $cartId = null): string
    {
        try {
            $order = $this->orderRepository->get($orderId);
        } catch (NoSuchEntityException $noSuchEntityException) {
            $this->logger->logException($noSuchEntityException);

            return 'Unable to load order';
        }

        if ($cartId && (int)$order->getQuoteId() !== $cartId) {
            return '';
        }

        /**
         * This is already checked on the order load
         * Authorization::afterLoad
         */
        if ($customerId && $customerId !== (int)$order->getCustomerId()) {
            return '';
        }

        return $this->paymentLink->getPaymentLinkFromOrder($order);
    }
}
