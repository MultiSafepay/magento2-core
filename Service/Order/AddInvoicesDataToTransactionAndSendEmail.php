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

namespace MultiSafepay\ConnectCore\Service\Order;

use Magento\Framework\Exception\MailException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\Api\TransactionManager;
use MultiSafepay\Api\Transactions\UpdateRequest;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\EmailSender;
use MultiSafepay\ConnectCore\Util\InvoiceUtil;
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
     * @var UpdateRequest
     */
    private $updateRequest;

    /**
     * @var InvoiceUtil
     */
    private $invoiceUtil;

    /**
     * AddInvoicesDataToTransactionAndSendEmail constructor.
     *
     * @param EmailSender $emailSender
     * @param UpdateRequest $updateRequest
     * @param Logger $logger
     * @param InvoiceUtil $invoiceUtil
     */
    public function __construct(
        EmailSender $emailSender,
        UpdateRequest $updateRequest,
        Logger $logger,
        InvoiceUtil $invoiceUtil
    ) {
        $this->emailSender = $emailSender;
        $this->updateRequest = $updateRequest;
        $this->logger = $logger;
        $this->invoiceUtil = $invoiceUtil;
    }

    /**
     * @param Order $order
     * @param Payment $payment
     * @param TransactionManager $transactionManager
     * @throws ClientExceptionInterface
     */
    public function execute(Order $order, Payment $payment, TransactionManager $transactionManager): void
    {
        $orderId = $order->getIncrementId();

        foreach ($this->invoiceUtil->getInvoicesByOrderId($order->getId()) as $invoice) {
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
