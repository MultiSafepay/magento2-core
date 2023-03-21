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
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\Api\TransactionManager;
use MultiSafepay\Api\Transactions\UpdateRequest;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;
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
     * AddInvoiceDataToTransaction constructor
     *
     * @param Logger $logger
     * @param UpdateRequest $updateRequest
     * @param SdkFactory $sdkFactory
     */
    public function __construct(
        Logger $logger,
        UpdateRequest $updateRequest,
        SdkFactory $sdkFactory
    ) {
        $this->logger = $logger;
        $this->updateRequest = $updateRequest;
        $this->sdkFactory = $sdkFactory;
    }

    /**
     * Send an update request to MultiSafepay with the newly added invoice data
     *
     * @param OrderInterface $order
     * @param array $transaction
     * @return array
     * @throws Exception
     */
    public function execute(OrderInterface $order, array $transaction): array
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

        /** @var Invoice $invoice */
        $invoice = $payment->getCreatedInvoice();
        $invoiceId = $invoice->getIncrementId();

        $updateRequest = $this->updateRequest->addData([
            "invoice_id" => $invoiceId,
        ]);

        try {
            $transactionManager = $this->sdkFactory->create((int)$order->getStoreId())->getTransactionManager();
            $transactionManager->update($orderId, $updateRequest)->getResponseData();
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
