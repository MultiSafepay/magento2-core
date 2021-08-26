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

namespace MultiSafepay\ConnectCore\Service\Invoice;

use Exception;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class CreateInvoiceByInvoiceData
{
    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * CreateInvoiceByInvoiceData constructor.
     *
     * @param TransactionFactory $transactionFactory
     */
    public function __construct(
        TransactionFactory $transactionFactory
    ) {
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param array $invoiceData
     * @return InvoiceInterface
     * @throws LocalizedException
     * @throws Exception
     */
    public function execute(
        OrderInterface $order,
        OrderPaymentInterface $payment,
        array $invoiceData
    ): InvoiceInterface {
        if (!$order->canInvoice()) {
            throw new LocalizedException(__("Invoice can't be created"));
        }

        /** @var InvoiceInterface $invoice */
        $invoice = $order->prepareInvoice($invoiceData);
        $invoice->register();
        $invoice->getOrder()->setIsInProcess(true);
        $payment->setInvoice($invoice);
        $payment->capture($invoice);

        if ($invoice->getIsPaid()) {
            $invoice->pay();
        }

        $payment->getOrder()->addRelatedObject($invoice);
        $payment->setCreatedInvoice($invoice);
        $this->transactionFactory->create()
            ->addObject($invoice)
            ->addObject($invoice->getOrder())
            ->save();

        return $invoice;
    }
}
