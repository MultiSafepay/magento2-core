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

namespace MultiSafepay\ConnectCore\Service\Order;

use Magento\Framework\Exception\MailException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\Api\TransactionManager;
use MultiSafepay\ConnectCore\Logger\Logger;
use Magento\Sales\Api\OrderRepositoryInterface;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\ConnectCore\Service\InvoiceService;
use MultiSafepay\ConnectCore\Service\EmailSender;
use MultiSafepay\Api\Transactions\UpdateRequest;
use Psr\Http\Client\ClientExceptionInterface;

class AddInvoicesDataToTransactionAndSendEmail
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var OrderRepositoryInterface
     */
    private $invoiceService;

    /**
     * @var EmailSender
     */
    private $emailSender;

    /**
     * AddInvoicesDataToTransactionAndSendEmail constructor.
     *
     * @param InvoiceService $invoiceService
     * @param EmailSender $emailSender
     * @param UpdateRequest $updateRequest
     * @param Logger $logger
     */
    public function __construct(
        InvoiceService $invoiceService,
        EmailSender $emailSender,
        UpdateRequest $updateRequest,
        Logger $logger
    ) {
        $this->invoiceService = $invoiceService;
        $this->emailSender = $emailSender;
        $this->updateRequest = $updateRequest;
        $this->logger = $logger;
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param TransactionManager $transactionManager
     * @throws ClientExceptionInterface
     */
    public function execute(
        OrderInterface $order,
        OrderPaymentInterface $payment,
        TransactionManager $transactionManager
    ): void {
        $orderId = $order->getIncrementId();

        foreach ($this->invoiceService->getInvoicesByOrderId($order->getId()) as $invoice) {
            $invoiceIncrementId = $invoice->getIncrementId();

            try {
                if ($this->emailSender->sendInvoiceEmail($payment, $invoice)) {
                    $this->logger->logInfoForOrder($orderId, __('Invoice email was sent.')->render(), Logger::DEBUG);
                }
            } catch (MailException $mailException) {
                $this->logger->logExceptionForOrder($orderId, $mailException, Logger::INFO);
            }

            $updateRequest = $this->updateRequest->addData([
                "invoice_id" => $invoiceIncrementId,
            ]);

            try {
                $transactionManager->update($orderId, $updateRequest)->getResponseData();
                $this->logger->logInfoForOrder(
                    $orderId,
                    'Invoice: ' . $invoiceIncrementId . ' update request has been sent to MultiSafepay.'
                );
            } catch (ApiException $e) {
                $this->logger->logUpdateRequestApiException($orderId, $e);
            }
        }
    }
}
