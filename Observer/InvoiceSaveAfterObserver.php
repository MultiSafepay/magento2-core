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
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
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
     * InvoiceSaveAfterObserver constructor.
     *
     * @param SdkFactory $sdkFactory
     * @param PaymentMethodUtil $paymentMethodUtil
     * @param AddInvoicesDataToTransactionAndSendEmail $addInvoicesDataToTransactionAndSendEmail
     */
    public function __construct(
        SdkFactory $sdkFactory,
        PaymentMethodUtil $paymentMethodUtil,
        AddInvoicesDataToTransactionAndSendEmail $addInvoicesDataToTransactionAndSendEmail
    ) {
        $this->sdkFactory = $sdkFactory;
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->addInvoicesDataToTransactionAndSendEmail = $addInvoicesDataToTransactionAndSendEmail;
    }

    /**
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

        if ($this->paymentMethodUtil->isMultisafepayOrder($order)
            && $order->getBaseTotalDue() === 0.0
            && $payment->getAdditionalInformation(PayMultisafepayOrder::INVOICE_CREATE_AFTER_PARAM_NAME)
        ) {
            $transactionManager = $this->sdkFactory->create((int)$order->getStoreId())->getTransactionManager();
            $this->addInvoicesDataToTransactionAndSendEmail->execute($order, $payment, $transactionManager);
        }
    }
}
