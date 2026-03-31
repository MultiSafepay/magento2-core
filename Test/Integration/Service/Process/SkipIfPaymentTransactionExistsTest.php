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

namespace MultiSafepay\ConnectCore\Test\Integration\Service\Process;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction as TransactionResource;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\CollectionFactory as TransactionCollectionFactory;
use MultiSafepay\ConnectCore\Service\Process\ProcessInterface;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\Process\SkipIfPaymentTransactionExists;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\ProcessUtil;
use PHPUnit\Framework\MockObject\Exception;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SkipIfPaymentTransactionExistsTest extends AbstractTestCase
{
    /**
     * Tests that if a payment transaction with the given PSP ID already exists for the order,
     * the process will return a response indicating success to stop further processing
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     *
     * @return void
     * @throws AlreadyExistsException
     * @throws Exception
     * @throws \Exception
     */
    public function testStopsWhenPaymentTransactionExists(): void
    {
        $order = $this->createOrderWithPayment('100000001');

        $this->createPaymentTransaction(
            (int)$order->getEntityId(),
            (int)$order->getPayment()->getId(),
            'psp_123'
        );

        $process = $this->createProcess();
        $response = $process->execute($order, ['transaction_id' => 'psp_123']);

        self::assertTrue($response[StatusOperationInterface::SUCCESS_PARAMETER]);
        self::assertTrue($response[ProcessUtil::STOP_PROCESSING]);
        self::assertFalse($response[ProcessInterface::SAVE_ORDER]);
    }

    /**
     * Tests that if a payment transaction with the given PSP ID does not exist for the order,
     * the process will return a response allowing processing to continue
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @throws Exception
     * @throws \Exception
     */
    public function testDoesNotStopWhenPaymentTransactionDoesNotExist(): void
    {
        $order = $this->createOrderWithPayment('100000002');
        $process = $this->createProcess();
        $response = $process->execute($order, ['transaction_id' => 'psp_missing']);

        self::assertTrue($response[StatusOperationInterface::SUCCESS_PARAMETER]);
        self::assertArrayNotHasKey(ProcessUtil::STOP_PROCESSING, $response);
    }

    /**
     * Tests that if the transaction ID is missing from the input,
     * the process will return a response allowing processing to continue
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @throws Exception
     * @throws \Exception
     */
    public function testDoesNotStopWhenPspIdMissing(): void
    {
        $order = $this->createOrderWithPayment('100000003');
        $process = $this->createProcess();
        $response = $process->execute($order, []);

        self::assertTrue($response[StatusOperationInterface::SUCCESS_PARAMETER]);
        self::assertArrayNotHasKey(ProcessUtil::STOP_PROCESSING, $response);
    }

    /**
     * Creates an instance of the SkipIfPaymentTransactionExists process with mocked dependencies
     *
     * @return SkipIfPaymentTransactionExists
     * @throws Exception
     */
    private function createProcess(): SkipIfPaymentTransactionExists
    {
        $loggerMock = $this->createMock(Logger::class);
        $loggerMock->method('logInfoForNotification');

        /** @var TransactionCollectionFactory $collectionFactory */
        $collectionFactory = $this->getObjectManager()->get(TransactionCollectionFactory::class);

        return new SkipIfPaymentTransactionExists($loggerMock, $collectionFactory);
    }

    /**
     * Creates an order with a payment method, but without any transactions, to be used in the tests
     *
     * @param string $incrementId
     * @return Order
     */
    private function createOrderWithPayment(string $incrementId): Order
    {
        /** @var Order $order */
        $order = $this->getObjectManager()->create(Order::class);

        $order->setIncrementId($incrementId);
        $order->setState(Order::STATE_NEW);
        $order->setStatus('pending');
        $order->setBaseGrandTotal(10.00);
        $order->setGrandTotal(10.00);
        $order->setBaseSubtotal(10.00);
        $order->setSubtotal(10.00);
        $order->setBaseCurrencyCode('EUR');
        $order->setOrderCurrencyCode('EUR');
        $order->setStoreCurrencyCode('EUR');

        /** @var Payment $payment */
        $payment = $this->getObjectManager()->create(Payment::class);
        $payment->setMethod('checkmo');
        $order->setPayment($payment);

        /** @var OrderRepositoryInterface $orderRepo */
        $orderRepo = $this->getObjectManager()->get(OrderRepositoryInterface::class);
        $orderRepo->save($order);

        return $orderRepo->get((int)$order->getEntityId());
    }

    /**
     * Creates a payment transaction with the given PSP ID for the specified order and payment, to be used in the tests
     *
     * @param int $orderId
     * @param int $paymentId
     * @param string $pspId
     * @return void
     * @throws AlreadyExistsException
     */
    private function createPaymentTransaction(int $orderId, int $paymentId, string $pspId): void
    {
        /** @var Transaction $txn */
        $txn = $this->getObjectManager()->create(Transaction::class);
        $txn->setOrderId($orderId);
        $txn->setPaymentId($paymentId);
        $txn->setTxnId($pspId);
        $txn->setTxnType(TransactionInterface::TYPE_CAPTURE);
        $txn->setIsClosed(1);

        /** @var TransactionResource $txnResource */
        $txnResource = $this->getObjectManager()->get(TransactionResource::class);
        $txnResource->save($txn);
    }
}
