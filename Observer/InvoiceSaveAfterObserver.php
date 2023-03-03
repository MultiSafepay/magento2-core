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

namespace MultiSafepay\ConnectCore\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;
use Psr\Http\Client\ClientExceptionInterface;
use MultiSafepay\ConnectCore\Service\Order\AddInvoicesDataToTransactionAndSendEmail;
use MultiSafepay\ConnectCore\Service\Order\PayMultisafepayOrder;

class InvoiceSaveAfterObserver implements ObserverInterface
{
    /**
     * @var SdkFactory
     */
    private $sdkFactory;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var AddInvoicesDataToTransactionAndSendEmail
     */
    private $addInvoicesDataToTransactionAndSendEmail;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * InvoiceSaveAfterObserver constructor.
     *
     * @param SdkFactory $sdkFactory
     * @param PaymentMethodUtil $paymentMethodUtil
     * @param AddInvoicesDataToTransactionAndSendEmail $addInvoicesDataToTransactionAndSendEmail
     * @param Logger $logger
     */
    public function __construct(
        SdkFactory $sdkFactory,
        PaymentMethodUtil $paymentMethodUtil,
        AddInvoicesDataToTransactionAndSendEmail $addInvoicesDataToTransactionAndSendEmail,
        Logger $logger
    ) {
        $this->sdkFactory = $sdkFactory;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->addInvoicesDataToTransactionAndSendEmail = $addInvoicesDataToTransactionAndSendEmail;
        $this->logger = $logger;
    }

    /**
     * Execute the observer for the sales_order_invoice_save_after event
     *
     * @param Observer $observer
     * @throws ClientExceptionInterface
     */
    public function execute(Observer $observer): void
    {
        $event = $observer->getEvent();
        /** @var InvoiceInterface $invoice */
        $invoice = $event->getInvoice();
        $order = $invoice->getOrder();
        /** @var OrderPaymentInterface $payment */
        $payment = $order->getPayment();

        if (!$this->paymentMethodUtil->isMultisafepayOrder($order)) {
            return;
        }

        if ($order->getBaseTotalDue() === 0.0
            && $payment->getAdditionalInformation(PayMultisafepayOrder::INVOICE_CREATE_AFTER_PARAM_NAME)
        ) {
            $transactionManager = $this->sdkFactory->create((int)$order->getStoreId())->getTransactionManager();
            $this->addInvoicesDataToTransactionAndSendEmail->execute($order, $payment, $transactionManager);

            return;
        }

        if ($invoice->getRequestedCaptureCase() === Invoice::CAPTURE_OFFLINE
            && $order->getState() === Order::STATE_PENDING_PAYMENT) {
            $order->setState(Order::STATE_NEW);
            $this->logger->logInfoForOrder(
                $order->getRealOrderId(),
                'Invoice has been captured offline, pending status has been applied'
            );
        }
    }
}
