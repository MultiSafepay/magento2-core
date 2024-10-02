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
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Container\InvoiceIdentity;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\CaptureUtil;
use MultiSafepay\ConnectCore\Util\InvoiceUtil;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class SendInvoice implements ProcessInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var InvoiceSender
     */
    private $invoiceSender;

    /**
     * @var InvoiceUtil
     */
    private $invoiceUtil;

    /**
     * @var CaptureUtil
     */
    private $captureUtil;

    /**
     * SendInvoice constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param InvoiceSender $invoiceSender
     * @param InvoiceUtil $invoiceUtil
     * @param CaptureUtil $captureUtil
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        InvoiceSender $invoiceSender,
        InvoiceUtil $invoiceUtil,
        CaptureUtil $captureUtil
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->invoiceSender = $invoiceSender;
        $this->invoiceUtil = $invoiceUtil;
        $this->captureUtil = $captureUtil;
    }

    /**
     * Send the invoice
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

        if ($this->captureUtil->isManualCaptureEnabled($payment)) {
            $this->logger->logInfoForNotification(
                $orderId,
                'No invoice e-mail sent, because manual capture is enabled',
                $transaction
            );

            return [StatusOperationInterface::SUCCESS_PARAMETER => true];
        }

        if ((bool)$this->scopeConfig->getValue(InvoiceIdentity::XML_PATH_EMAIL_ENABLED) === false) {
            $this->logger->logInfoForNotification(
                $orderId,
                'No invoice e-mail sent, because the setting is disabled',
                $transaction
            );

            return [StatusOperationInterface::SUCCESS_PARAMETER => true];
        }

        $invoice = $payment->getCreatedInvoice();

        if (!$invoice) {
            $this->logger->logInfoForNotification(
                $orderId,
                'Invoice not found, trying to retrieve through repository',
                $transaction
            );

            $invoice = $this->invoiceUtil->getLastCreatedInvoiceByOrderId($orderId);
        }

        if (!$invoice) {
            $this->logger->logInfoForNotification(
                $orderId,
                'Order has no invoices, can not send the invoice e-mail',
                $transaction
            );

            return [StatusOperationInterface::SUCCESS_PARAMETER => true];
        }

        $disallowedMethods = $this->invoiceUtil->getDisallowedPaymentMethods();

        if (!$invoice->getEmailSent() && !in_array($payment->getMethod(), $disallowedMethods, true)) {
            $this->invoiceSender->send($invoice);
            $this->logger->logInfoForNotification(
                $orderId,
                'Invoice e-mail has been sent',
                $transaction
            );

            return [StatusOperationInterface::SUCCESS_PARAMETER => true];
        }

        $this->logger->logInfoForNotification(
            $orderId,
            'Invoice e-mail was not sent',
            $transaction
        );

        return [StatusOperationInterface::SUCCESS_PARAMETER => true];
    }
}
