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

namespace MultiSafepay\ConnectCore\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\Api\Initializer\OrderRequestInitializer;
use MultiSafepay\ConnectCore\Model\PaymentLink;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\Exception\InvalidApiKeyException;
use Psr\Http\Client\ClientExceptionInterface;

class OrderPlaceAfterObserver implements ObserverInterface
{
    /**
     * @var OrderRequestInitializer
     */
    private $orderRequestInitializer;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var PaymentLink
     */
    private $paymentLink;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * OrderPlaceAfterObserver constructor.
     *
     * @param Logger $logger
     * @param ManagerInterface $messageManager
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderRequestInitializer $orderRequestInitializer
     * @param PaymentLink $paymentLink
     */
    public function __construct(
        Logger $logger,
        ManagerInterface $messageManager,
        OrderRepositoryInterface $orderRepository,
        OrderRequestInitializer $orderRequestInitializer,
        PaymentLink $paymentLink
    ) {
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->orderRepository = $orderRepository;
        $this->orderRequestInitializer = $orderRequestInitializer;
        $this->paymentLink = $paymentLink;
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws ClientExceptionInterface
     */
    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $observer->getEvent()->getOrder();

        /** @var Payment $payment */
        $payment = $order->getPayment();

        $isMultiSafepay = $payment->getMethodInstance()->getConfigData('is_multisafepay');

        if ($isMultiSafepay) {
            try {
                $transaction = $this->orderRequestInitializer->initialize($order);
            } catch (InvalidApiKeyException $invalidApiKeyException) {
                $this->logger->logInvalidApiKeyException($invalidApiKeyException);
                $msg = __('The order can not be created, because the MultiSafepay API key is invalid');
                $this->messageManager->addErrorMessage($msg);
                return;
            } catch (ApiException $apiException) {
                $this->logger->logPaymentLinkError($order->getIncrementId(), $apiException);
                $msg = __('The order can not be created, because there was a MultiSafepay error: ') .
                    $apiException->getCode() . ' ' . $apiException->getMessage();
                $this->messageManager->addErrorMessage($msg);
                return;
            }

            $paymentUrl = $transaction->getPaymentUrl();
            $this->paymentLink->addToAdditionalInformation($payment, $paymentUrl);

            $msg = __('Payment link for this transaction: ') . $paymentUrl;
            $order->addCommentToStatusHistory($msg);
            $this->orderRepository->save($order);
        }
    }
}
