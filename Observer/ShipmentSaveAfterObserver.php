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
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use MultiSafepay\Api\Transactions\UpdateRequest;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;
use MultiSafepay\Exception\ApiException;
use Psr\Http\Client\ClientExceptionInterface;
use MultiSafepay\ConnectCore\Service\InvoiceService;
use MultiSafepay\ConnectCore\Util\CaptureUtil;
use MultiSafepay\ConnectCore\Util\ShipmentUtil;
use Magento\Sales\Api\OrderRepositoryInterface;
use MultiSafepay\Api\TransactionManager;
use Exception;

class ShipmentSaveAfterObserver implements ObserverInterface
{

    /**
     * @var SdkFactory
     */
    private $sdkFactory;

    /**
     * @var UpdateRequest
     */
    private $updateRequest;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var InvoiceService
     */
    private $invoiceService;

    /**
     * @var CaptureUtil
     */
    private $captureUtil;

    /**
     * @var ShipmentUtil
     */
    private $shipmentUtil;

    /**
     * ShipmentSaveAfterObserver constructor.
     *
     * @param SdkFactory $sdkFactory
     * @param Logger $logger
     * @param ManagerInterface $messageManager
     * @param UpdateRequest $updateRequest
     * @param PaymentMethodUtil $paymentMethodUtil
     */
    public function __construct(
        SdkFactory $sdkFactory,
        Logger $logger,
        ManagerInterface $messageManager,
        UpdateRequest $updateRequest,
        PaymentMethodUtil $paymentMethodUtil,
        OrderRepositoryInterface $orderRepository,
        InvoiceService $invoiceService,
        CaptureUtil $captureUtil,
        ShipmentUtil $shipmentUtil
    ) {
        $this->sdkFactory = $sdkFactory;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->updateRequest = $updateRequest;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->orderRepository = $orderRepository;
        $this->invoiceService = $invoiceService;
        $this->captureUtil = $captureUtil;
        $this->shipmentUtil = $shipmentUtil;
    }


    /**
     * @param Observer $observer
     * @throws ClientExceptionInterface
     */
    public function execute(Observer $observer): void
    {
        $event = $observer->getEvent();
        /** @var ShipmentInterface $shipment */
        $shipment = $event->getShipment();
        $order = $shipment->getOrder();

        if ($this->paymentMethodUtil->isMultisafepayOrder($order)) {
            $orderIncrementId = $order->getIncrementId();
            $transactionManager = $this->sdkFactory->create((int)$order->getStoreId())->getTransactionManager();
            $transaction = $transactionManager->get($order->getIncrementId())->getData();
            $payment = $order->getPayment();

            try {
                if ($this->captureUtil->isCaptureManualPayment($payment)
                    && $this->captureUtil->isCaptureManualTransaction($transaction)
                ) {
                    if ($this->invoiceService->createInvoiceAfterShipment($order, $shipment, $payment)) {
                        $this->orderRepository->save($order);

                        if ($lastCreatedInvoice = $this->invoiceService->getLastCreatedInvoiceByOrderId($order->getId())
                        ) {
                            $this->invoiceService->sendInvoiceEmail($payment, $lastCreatedInvoice);
                            $successMessage = __(
                                'The manual capture invoice %1 was created at MultiSafepay',
                                $lastCreatedInvoice->getIncrementId()
                            );
                            $this->logger->logInfoForOrder($orderIncrementId, $successMessage->render());
                            $this->messageManager->addSuccessMessage($successMessage);
                        }
                    }

                    if ($this->shipmentUtil->isOrderShippedPartially($order)) {
                        return;
                    }
                }
            } catch (Exception $exception) {
                $this->logger->logExceptionForOrder($orderIncrementId, $exception);
                $this->messageManager->addErrorMessage(
                    __('The manual capture invoice could not be created at MultiSafepay, please check the logs.')
                );

                return;
            }

            $this->addShippingToTransaction($shipment, $order, $transactionManager);
        }
    }

    ///**
    // * @param ShipmentInterface $shipment
    // * @param OrderInterface $order
    // * @throws ClientExceptionInterface
    // */
    //public function addShippingToTransaction(
    //    ShipmentInterface $shipment,
    //    OrderInterface $order
    //): void {
    //    if ($this->paymentMethodUtil->isMultisafepayOrder($order)) {
    //        $transactionManager = $this->sdkFactory->create((int)$order->getStoreId())->getTransactionManager();
    //
    //        $updateRequest = $this->updateRequest->addData([
    //                "tracktrace_code" => $this->getTrackingNumber($shipment),
    //                "carrier" => $order->getShippingDescription(),
    //                "ship_date" => $shipment->getCreatedAt(),
    //                "reason" => 'Shipped'
    //            ]);
    //
    //        $orderId = $order->getIncrementId();
    //
    //        try {
    //            $transactionManager->update($orderId, $updateRequest)->getResponseData();
    //        } catch (ApiException $apiException) {
    //            $this->logger->logUpdateRequestApiException($orderId, $apiException);
    //
    //            $msg = __('The order status could not be updated at MultiSafepay.
    //            It can be manually updated in MultiSafepay Control');
    //
    //            $this->messageManager->addErrorMessage($msg);
    //            return;
    //        }
    //
    //        $msg = __('The order status has succesfully been updated at MultiSafepay');
    //        $this->messageManager->addSuccessMessage($msg);
    //    }
    //}

    /**
     * @param ShipmentInterface $shipment
     * @param OrderInterface $order
     * @param TransactionManager $transactionManager
     * @throws ClientExceptionInterface
     */
    public function addShippingToTransaction(
        ShipmentInterface $shipment,
        OrderInterface $order,
        TransactionManager $transactionManager
    ): void {
        $orderId = $order->getIncrementId();
        $updateRequest = $this->updateRequest->addData(
            $this->shipmentUtil->getShipmentApiRequestData($order, $shipment)
        );

        try {
            $transactionManager->update($orderId, $updateRequest)->getResponseData();
        } catch (ApiException $apiException) {
            $this->logger->logUpdateRequestApiException($orderId, $apiException);
            $msg = __('The order status could not be updated at MultiSafepay.
                It can be manually updated in MultiSafepay Control');

            $this->messageManager->addErrorMessage($msg);

            return;
        }

        $msg = __('The order status has succesfully been updated at MultiSafepay');
        $this->messageManager->addSuccessMessage($msg);
    }
}
