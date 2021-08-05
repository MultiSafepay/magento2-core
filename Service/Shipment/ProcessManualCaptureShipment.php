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

namespace MultiSafepay\ConnectCore\Service\Shipment;

use Exception;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\EmailSender;
use MultiSafepay\ConnectCore\Service\Invoice\CreateInvoiceAfterShipment;
use MultiSafepay\ConnectCore\Util\InvoiceUtil;
use MultiSafepay\ConnectCore\Util\ShipmentUtil;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProcessManualCaptureShipment
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ShipmentUtil
     */
    private $shipmentUtil;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var CreateInvoiceAfterShipment
     */
    private $createInvoiceAfterShipment;

    /**
     * @var EmailSender
     */
    private $emailSender;

    /**
     * @var InvoiceUtil
     */
    private $invoiceUtil;

    /**
     * @var AddShippingToTransaction
     */
    private $addShippingToTransaction;

    /**
     * ProcessManualCaptureShipment constructor.
     *
     * @param Logger $logger
     * @param ShipmentUtil $shipmentUtil
     * @param ManagerInterface $messageManager
     * @param OrderRepositoryInterface $orderRepository
     * @param CreateInvoiceAfterShipment $createInvoiceAfterShipment
     * @param EmailSender $emailSender
     * @param InvoiceUtil $invoiceUtil
     * @param AddShippingToTransaction $addShippingToTransaction
     */
    public function __construct(
        Logger $logger,
        ShipmentUtil $shipmentUtil,
        ManagerInterface $messageManager,
        OrderRepositoryInterface $orderRepository,
        CreateInvoiceAfterShipment $createInvoiceAfterShipment,
        EmailSender $emailSender,
        InvoiceUtil $invoiceUtil,
        AddShippingToTransaction $addShippingToTransaction
    ) {
        $this->logger = $logger;
        $this->shipmentUtil = $shipmentUtil;
        $this->messageManager = $messageManager;
        $this->orderRepository = $orderRepository;
        $this->createInvoiceAfterShipment = $createInvoiceAfterShipment;
        $this->emailSender = $emailSender;
        $this->invoiceUtil = $invoiceUtil;
        $this->addShippingToTransaction = $addShippingToTransaction;
    }

    /**
     * @param ShipmentInterface $shipment
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @throws ClientExceptionInterface
     */
    public function execute(
        ShipmentInterface $shipment,
        OrderInterface $order,
        OrderPaymentInterface $payment
    ): void {
        $orderIncrementId = $order->getIncrementId();

        try {
            if ($this->createInvoiceAfterShipment->execute($order, $shipment, $payment)) {
                $this->orderRepository->save($order);

                if ($lastCreatedInvoice = $this->invoiceUtil->getLastCreatedInvoiceByOrderId($order->getId())) {
                    $this->sendInvoiceEmail($payment, $lastCreatedInvoice);
                    $successMessage = __(
                        'The manual capture invoice %1 was created at MultiSafepay',
                        $lastCreatedInvoice->getIncrementId()
                    );
                    $this->logger->logInfoForOrder($orderIncrementId, $successMessage->render());
                    $this->messageManager->addSuccessMessage($successMessage);
                }
            }

            if ($this->shipmentUtil->isOrderShippedPartially($order)) {
                /**
                 * @todo update parent Transaction status to status completed for fully paid transaction
                 */
                //if (!$order->getBaseTotalDue()) {
                //    //$this->completeParentTransactionStatus($order, $transactionManager);
                //}

                return;
            }
        } catch (Exception $exception) {
            $this->logger->logExceptionForOrder($orderIncrementId, $exception);
            $this->messageManager->addErrorMessage(
                __('The manual capture invoice could not be created at MultiSafepay, please check the logs.')
            );

            return;
        }

        $this->addShippingToTransaction->execute($shipment, $order);
    }

    /**
     * @param OrderPaymentInterface $payment
     * @param InvoiceInterface $invoice
     * @throws Exception
     */
    public function sendInvoiceEmail(OrderPaymentInterface $payment, InvoiceInterface $invoice): void
    {
        $orderIncrementId = $invoice->getOrder()->getIncrementId();

        try {
            if ($this->emailSender->sendInvoiceEmail($payment, $invoice)) {
                $this->logger->logInfoForOrder(
                    $orderIncrementId,
                    __('Email for invoice %1 was sent.', $invoice->getIncrementId())->render()
                );
            }
        } catch (MailException $mailException) {
            $this->logger->logExceptionForOrder($orderIncrementId, $mailException, Logger::INFO);
        }
    }

    //private function completeParentTransactionStatus(
    //    OrderInterface $order,
    //    TransactionManager $transactionManager
    //): void {
    //    //$orderId = $order->getIncrementId();
    //    //$errorMessage = __('The order status could not be updated at MultiSafepay.');
    //    //
    //    //try {
    //    //    $transactionManager->update(
    //    //        $orderId,
    //    //        $this->updateRequest->addData(["financial_status" => TransactionStatus::COMPLETED])
    //    //    )->getResponseData();
    //    //} catch (ApiException $apiException) {
    //    //    $this->logger->logUpdateRequestApiException($orderId, $apiException);
    //    //    $this->messageManager->addErrorMessage($errorMessage);
    //    //
    //    //    return;
    //    //} catch (ClientExceptionInterface $clientException) {
    //    //    $this->logger->logClientException($orderId, $clientException);
    //    //    $this->messageManager->addErrorMessage($errorMessage);
    //    //
    //    //    return;
    //    //}
    //    //
    //    //$msg = __('The parent order status has succesfully been updated at MultiSafepay');
    //    //$this->messageManager->addSuccessMessage($msg);
    //}
}
