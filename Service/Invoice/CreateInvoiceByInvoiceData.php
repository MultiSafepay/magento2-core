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

namespace MultiSafepay\ConnectCore\Service\Invoice;

use Exception;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Exception\CouldNotInvoiceException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\ConnectCore\Logger\Logger;

class CreateInvoiceByInvoiceData
{
    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * CreateInvoiceByInvoiceData constructor.
     *
     * @param TransactionFactory $transactionFactory
     * @param Logger $logger
     */
    public function __construct(
        TransactionFactory $transactionFactory,
        Logger $logger
    ) {
        $this->transactionFactory = $transactionFactory;
        $this->logger = $logger;
    }

    /**
     * @param Order $order
     * @param Payment $payment
     * @param array $invoiceData
     * @return InvoiceInterface|null
     * @throws CouldNotInvoiceException
     * @throws Exception
     */
    public function execute(Order $order, Payment $payment, array $invoiceData): ?InvoiceInterface
    {
        $orderIncrementId = $order->getIncrementId();

        if (!$order->canInvoice()) {
            $message = __("Invoice can't be created");
            $this->logger->logInfoForOrder($orderIncrementId, $message->render());

            throw new CouldNotInvoiceException($message);
        }

        try {
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
        } catch (Exception $exception) {
            $this->logger->logExceptionForOrder($orderIncrementId, $exception);

            throw new CouldNotInvoiceException(__($exception->getMessage()));
        }
    }
}
