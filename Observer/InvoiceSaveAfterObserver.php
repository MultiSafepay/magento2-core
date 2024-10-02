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

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\OrderStatusUtil;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;
use MultiSafepay\ConnectCore\Service\Process\CreateInvoice;
use Psr\Http\Client\ClientExceptionInterface;
use MultiSafepay\ConnectCore\Service\Order\AddInvoicesDataToTransactionAndSendEmail;

class InvoiceSaveAfterObserver implements ObserverInterface
{
    public const ORDER_STATES = [
        Order::STATE_PENDING_PAYMENT,
        Order::STATE_NEW,
    ];

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
     * @var OrderStatusUtil
     */
    private $orderStatusUtil;

    /**
     * InvoiceSaveAfterObserver constructor.
     *
     * @param SdkFactory $sdkFactory
     * @param PaymentMethodUtil $paymentMethodUtil
     * @param AddInvoicesDataToTransactionAndSendEmail $addInvoicesDataToTransactionAndSendEmail
     * @param Logger $logger
     * @param OrderStatusUtil $orderStatusUtil
     */
    public function __construct(
        SdkFactory $sdkFactory,
        PaymentMethodUtil $paymentMethodUtil,
        AddInvoicesDataToTransactionAndSendEmail $addInvoicesDataToTransactionAndSendEmail,
        Logger $logger,
        OrderStatusUtil $orderStatusUtil
    ) {
        $this->sdkFactory = $sdkFactory;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->addInvoicesDataToTransactionAndSendEmail = $addInvoicesDataToTransactionAndSendEmail;
        $this->logger = $logger;
        $this->orderStatusUtil = $orderStatusUtil;
    }

    /**
     * Execute the observer for the sales_order_invoice_save_after event
     *
     * @param Observer $observer
     * @throws ClientExceptionInterface
     * @throws LocalizedException
     * @throws Exception
     */
    public function execute(Observer $observer): void
    {
        $event = $observer->getEvent();

        /** @var Invoice $invoice */
        $invoice = $event->getInvoice();
        $order = $invoice->getOrder();

        /** @var Payment $payment */
        $payment = $order->getPayment();

        if (!$this->paymentMethodUtil->isMultisafepayOrder($order)) {
            return;
        }

        if ($order->getBaseTotalDue() === 0.0
            && $payment->getAdditionalInformation(CreateInvoice::INVOICE_CREATE_AFTER)
        ) {
            $transactionManager = $this->sdkFactory->create((int)$order->getStoreId())->getTransactionManager();
            $this->addInvoicesDataToTransactionAndSendEmail->execute($order, $payment, $transactionManager);

            return;
        }

        if (!in_array($order->getState(), self::ORDER_STATES, true)) {
            return;
        }

        if ($invoice->getRequestedCaptureCase() === Invoice::CAPTURE_OFFLINE || $invoice->canCapture() === false) {
            $order->setState(Order::STATE_PROCESSING);
            $order->setStatus($this->orderStatusUtil->getProcessingStatus($order));
            $this->logger->logInfoForOrder(
                $order->getRealOrderId(),
                'Invoice has been created and processing status has been applied by InvoiceSaveAfterObserver'
            );
        }
    }
}
