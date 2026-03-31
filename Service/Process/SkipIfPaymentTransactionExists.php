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
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\CollectionFactory as TransactionCollectionFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;
use MultiSafepay\ConnectCore\Util\ProcessUtil;

class SkipIfPaymentTransactionExists implements ProcessInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var TransactionCollectionFactory
     */
    private $transactionCollectionFactory;

    public function __construct(
        Logger $logger,
        TransactionCollectionFactory $transactionCollectionFactory
    ) {
        $this->transactionCollectionFactory = $transactionCollectionFactory;
        $this->logger = $logger;
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
}
