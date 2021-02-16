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
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\PaymentLink;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\Exception\InvalidApiKeyException;
use Psr\Http\Client\ClientExceptionInterface;

class OrderPlaceAfterObserver implements ObserverInterface
{
    /**
     * @var Logger
     */
    private $logger;

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
     * @param PaymentLink $paymentLink
     */
    public function __construct(
        Logger $logger,
        ManagerInterface $messageManager,
        PaymentLink $paymentLink
    ) {
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->paymentLink = $paymentLink;
    }

    /**
     * @param Observer $observer
     * @throws ClientExceptionInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
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
                $paymentUrl = $this->paymentLink->getPaymentLinkByOrder($order);
                $this->paymentLink->addPaymentLink($order, $paymentUrl);
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
        }
    }
}
