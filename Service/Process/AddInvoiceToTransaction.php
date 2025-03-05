<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * Copyright © 2022 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Service\Process;

use Exception;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\Api\Transactions\UpdateRequest;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;
use MultiSafepay\ConnectCore\Util\CaptureUtil;
use MultiSafepay\ConnectCore\Util\InvoiceUrlUtil;
use MultiSafepay\Exception\ApiException;
use Psr\Http\Client\ClientExceptionInterface;

class AddInvoiceToTransaction implements ProcessInterface
{
    /**
     * @var UpdateRequest
     */
    private $updateRequest;

    /**
     * @var SdkFactory
     */
    private $sdkFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CaptureUtil
     */
    private $captureUtil;

    /**
     * @var InvoiceUrlUtil
     */
    private $invoiceUrlUtil;

    /**
     * AddInvoiceDataToTransaction constructor
     *
     * @param Logger $logger
     * @param UpdateRequest $updateRequest
     * @param SdkFactory $sdkFactory
     * @param CaptureUtil $captureUtil
     * @param InvoiceUrlUtil $invoiceUrlUtil
     */
    public function __construct(
        Logger $logger,
        UpdateRequest $updateRequest,
        SdkFactory $sdkFactory,
        CaptureUtil $captureUtil,
        InvoiceUrlUtil $invoiceUrlUtil
    ) {
        $this->logger = $logger;
        $this->updateRequest = $updateRequest;
        $this->sdkFactory = $sdkFactory;
        $this->captureUtil = $captureUtil;
        $this->invoiceUrlUtil = $invoiceUrlUtil;
    }

    /**
     * Send an update request to MultiSafepay with the newly added invoice data
     *
     * @param Order $order
     * @param array $transaction
     * @return array
     * @throws Exception
     */
    public function execute(Order $order, array $transaction): array
    {
        $orderId = $order->getIncrementId();

        /** @var Payment $payment */
        $payment = $order->getPayment();

        if ($payment === null) {
            $this->logger->logInfoForNotification(
                $orderId,
                'No invoice e-mail sent, because the payment was not found',
                $transaction,
                Logger::WARNING
            );

            return [StatusOperationInterface::SUCCESS_PARAMETER => true];
        }

        if ($this->captureUtil->isCaptureManualTransaction($transaction)) {
            $this->logger->logInfoForNotification(
                $orderId,
                'No invoice update sent to MultiSafepay, because manual capture is enabled',
                $transaction
            );

            return [StatusOperationInterface::SUCCESS_PARAMETER => true];
        }

        /** @var Invoice $invoice */
        $invoice = $payment->getCreatedInvoice();

        if (!$invoice) {
            $this->logger->logInfoForNotification(
                $orderId,
                'Invoice not found, could not update at MultiSafepay, skipping action..',
                $transaction
            );

            return [StatusOperationInterface::SUCCESS_PARAMETER => true];
        }

        $invoiceId = $invoice->getIncrementId() ?? '';

        if (!$invoiceId) {
            $this->logger->logInfoForNotification(
                $orderId,
                'Invoice ID not found, could not update at MultiSafepay',
                $transaction
            );

            return [StatusOperationInterface::SUCCESS_PARAMETER => true];
        }

        $this->updateRequest->addData(["invoice_id" => $invoiceId]);

        $invoiceUrl = $this->invoiceUrlUtil->getInvoiceUrl($order, $invoice);

        if ($invoiceUrl) {
            $this->updateRequest->addData(["invoice_url" => $invoiceUrl]);
        }

        try {
            $transactionManager = $this->sdkFactory->create((int)$order->getStoreId())->getTransactionManager();
            $transactionManager->update($orderId, $this->updateRequest)->getResponseData();
            $this->logger->logInfoForNotification(
                $orderId,
                'Invoice: ' . $invoiceId . ' update request has been sent to MultiSafepay.',
                $transaction
            );
        } catch (ApiException | ClientExceptionInterface | Exception $exception) {
            $this->logger->logNotificationException($orderId, $transaction, $exception);

            return [
                StatusOperationInterface::SUCCESS_PARAMETER => false,
                StatusOperationInterface::MESSAGE_PARAMETER => 'Exception occurred when trying to send the invoice data'
            ];
        }

        return [StatusOperationInterface::SUCCESS_PARAMETER => true];
    }
}
