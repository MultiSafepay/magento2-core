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

use Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\MailException;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use MultiSafepay\Api\TransactionManager;
use MultiSafepay\Api\Transactions\UpdateRequest;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\EmailSender;
use MultiSafepay\Exception\ApiException;
use Psr\Http\Client\ClientExceptionInterface;

class AddInvoicesDataToTransactionAndSendEmail
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var EmailSender
     */
    private $emailSender;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var UpdateRequest
     */
    private $updateRequest;

    /**
     * @var InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    /**
     * AddInvoicesDataToTransactionAndSendEmail constructor.
     *
     * @param EmailSender $emailSender
     * @param UpdateRequest $updateRequest
     * @param Logger $logger
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param InvoiceRepositoryInterface $invoiceRepository
     */
    public function __construct(
        EmailSender $emailSender,
        UpdateRequest $updateRequest,
        Logger $logger,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        InvoiceRepositoryInterface $invoiceRepository
    ) {
        $this->emailSender = $emailSender;
        $this->updateRequest = $updateRequest;
        $this->logger = $logger;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->invoiceRepository = $invoiceRepository;
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param TransactionManager $transactionManager
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function execute(
        OrderInterface $order,
        OrderPaymentInterface $payment,
        TransactionManager $transactionManager
    ): void {
        $orderId = $order->getIncrementId();

        foreach ($this->getInvoicesByOrderId($order->getId()) as $invoice) {
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

    /**
     * @param string $orderId
     * @return InvoiceInterface[]
     */
    private function getInvoicesByOrderId(string $orderId): array
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('order_id', $orderId)->create();

        return $this->invoiceRepository->getList($searchCriteria)->getItems();
    }
}
