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

namespace MultiSafepay\ConnectCore\Service\Process;

use Exception;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory as InvoiceCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\CollectionFactory as TransactionCollectionFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;
use MultiSafepay\ConnectCore\Util\ProcessUtil;

class SkipIfPaymentTransactionExists implements ProcessInterface
{
    private const UNPROCESSED_ORDER_STATES = [
        Order::STATE_NEW,
        Order::STATE_PENDING_PAYMENT,
        Order::STATE_PAYMENT_REVIEW,
        Order::STATE_CANCELED
    ];

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var TransactionCollectionFactory
     */
    private $transactionCollectionFactory;

    /**
     * @var InvoiceCollectionFactory
     */
    private $invoiceCollectionFactory;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    public function __construct(
        Logger $logger,
        TransactionCollectionFactory $transactionCollectionFactory,
        InvoiceCollectionFactory $invoiceCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory
    ) {
        $this->transactionCollectionFactory = $transactionCollectionFactory;
        $this->logger = $logger;
        $this->invoiceCollectionFactory = $invoiceCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    /**
     * @param Order $order
     * @param array $transaction
     * @return array|true[]
     * @throws Exception
     */
    public function execute(Order $order, array $transaction): array
    {
        $orderIncrementId = (string)($order->getIncrementId() ?? 'unknown');
        $pspId = (string)($transaction['transaction_id'] ?? '');

        if ($pspId === '') {
            return [StatusOperationInterface::SUCCESS_PARAMETER => true];
        }

        /** @var Payment $payment */
        $payment = $order->getPayment();

        if ($payment === null || !$payment->getId()) {
            return [StatusOperationInterface::SUCCESS_PARAMETER => true];
        }

        $collection = $this->transactionCollectionFactory->create();
        $collection->addPaymentIdFilter((int)$payment->getId());
        $collection->addFieldToFilter('txn_id', $pspId);

        /** @var Transaction $transactionItem */
        $transactionItem = $collection->getFirstItem();

        if (!$transactionItem->getId()) {
            return [StatusOperationInterface::SUCCESS_PARAMETER => true];
        }

        if ($transactionItem->getTxnType() === TransactionInterface::TYPE_CAPTURE
            && $this->isOrderUnprocessed((int)$order->getEntityId())
        ) {
            $this->logger->logInfoForNotification(
                $orderIncrementId,
                'Capture transaction already exists, but the order was not invoiced or processed; continuing.',
                $transaction
            );

            return [StatusOperationInterface::SUCCESS_PARAMETER => true];
        }

        $this->logger->logInfoForNotification(
            $orderIncrementId,
            sprintf(
                'Payment transaction "%s" already exists;
                 treating webhook as duplicate and skipping further processing.',
                $pspId
            ),
            $transaction
        );

        return [
            StatusOperationInterface::SUCCESS_PARAMETER => true,
            ProcessUtil::STOP_PROCESSING => true,
            self::SAVE_ORDER => false
        ];
    }

    /**
     * Check if the order has no invoice and is still in a state before payment in the database
     *
     * The capture transaction is saved before the order and its invoice, so its existence alone does not prove that
     * an earlier notification finished. Both are read from the database because the order object was loaded before
     * the notification lock, while another notification may have been processing it.
     *
     * @param int $orderId
     * @return bool
     */
    private function isOrderUnprocessed(int $orderId): bool
    {
        $invoiceCollection = $this->invoiceCollectionFactory->create();
        $invoiceCollection->setOrderFilter($orderId);

        if ($invoiceCollection->getSize() > 0) {
            return false;
        }

        $orderCollection = $this->orderCollectionFactory->create();
        $orderCollection->addFieldToSelect('state');
        $orderCollection->addFieldToFilter('entity_id', $orderId);

        return in_array($orderCollection->getFirstItem()->getData('state'), self::UNPROCESSED_ORDER_STATES, true);
    }
}
