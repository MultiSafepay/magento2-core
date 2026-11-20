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

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction as TransactionResource;
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
     * Tests that a capture transaction for an order that is already processing stops further processing
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     *
     * @return void
     * @throws AlreadyExistsException
     * @throws Exception
     * @throws \Exception
     */
    public function testStopsWhenPaymentTransactionExistsAndOrderIsProcessing(): void
    {
        $order = $this->createOrderWithPayment('100000001', Order::STATE_PROCESSING);
        $this->createPaymentTransaction($order, 'psp_123');

        $this->assertProcessingStops($this->createProcess()->execute($order, ['transaction_id' => 'psp_123']));
    }

    /**
     * Tests that a capture transaction for an invoiced order stops further processing
     *
     * @magentoDataFixture Magento/Sales/_files/invoice.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     *
     * @return void
     * @throws AlreadyExistsException
     * @throws Exception
     * @throws \Exception
     */
    public function testStopsWhenPaymentTransactionExistsAndOrderIsInvoiced(): void
    {
        /** @var Order $order */
        $order = $this->getObjectManager()->create(Order::class)->loadByIncrementId('100000001');
        $this->setOrderStateInDatabase((int)$order->getEntityId(), Order::STATE_PENDING_PAYMENT);
        $this->createPaymentTransaction($order, 'psp_456');

        $this->assertProcessingStops($this->createProcess()->execute($order, ['transaction_id' => 'psp_456']));
    }

    /**
     * Tests that a capture transaction for an order without invoice in pending_payment continues processing
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     *
     * @return void
     * @throws AlreadyExistsException
     * @throws Exception
     * @throws \Exception
     */
    public function testDoesNotStopWhenCaptureTransactionExistsButOrderIsNotProcessed(): void
    {
        $order = $this->createOrderWithPayment('100000004', Order::STATE_PENDING_PAYMENT);
        $this->createPaymentTransaction($order, 'psp_789');

        $this->assertProcessingContinues($this->createProcess()->execute($order, ['transaction_id' => 'psp_789']));
    }

    /**
     * Tests that a capture transaction for an order without invoice in payment_review continues processing
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     *
     * @return void
     * @throws AlreadyExistsException
     * @throws Exception
     * @throws \Exception
     */
    public function testDoesNotStopWhenCaptureTransactionExistsAndOrderIsInPaymentReview(): void
    {
        $order = $this->createOrderWithPayment('100000005', Order::STATE_PAYMENT_REVIEW);
        $this->createPaymentTransaction($order, 'psp_790');

        $this->assertProcessingContinues($this->createProcess()->execute($order, ['transaction_id' => 'psp_790']));
    }

    /**
     * Tests that a capture transaction for a canceled order without invoice continues processing
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     *
     * @return void
     * @throws AlreadyExistsException
     * @throws Exception
     * @throws \Exception
     */
    public function testDoesNotStopWhenCaptureTransactionExistsAndOrderIsCanceled(): void
    {
        $order = $this->createOrderWithPayment('100000006', Order::STATE_CANCELED);
        $this->createPaymentTransaction($order, 'psp_791');

        $this->assertProcessingContinues($this->createProcess()->execute($order, ['transaction_id' => 'psp_791']));
    }

    /**
     * Tests that a capture transaction for an order on hold without invoice stops further processing
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     *
     * @return void
     * @throws AlreadyExistsException
     * @throws Exception
     * @throws \Exception
     */
    public function testStopsWhenCaptureTransactionExistsAndOrderIsOnHold(): void
    {
        $order = $this->createOrderWithPayment('100000007', Order::STATE_HOLDED);
        $this->createPaymentTransaction($order, 'psp_792');

        $this->assertProcessingStops($this->createProcess()->execute($order, ['transaction_id' => 'psp_792']));
    }

    /**
     * Tests that an authorization transaction for an order without invoice stops further processing
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     *
     * @return void
     * @throws AlreadyExistsException
     * @throws Exception
     * @throws \Exception
     */
    public function testStopsWhenAuthorizationTransactionExistsWithoutInvoice(): void
    {
        $order = $this->createOrderWithPayment('100000008', Order::STATE_PENDING_PAYMENT);
        $this->createPaymentTransaction($order, 'psp_793', TransactionInterface::TYPE_AUTH);

        $this->assertProcessingStops($this->createProcess()->execute($order, ['transaction_id' => 'psp_793']));
    }

    /**
     * Tests that the order state is read from the database instead of the order object passed in
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     *
     * @return void
     * @throws AlreadyExistsException
     * @throws Exception
     * @throws \Exception
     */
    public function testStopsWhenOrderObjectIsStaleButOrderIsProcessedInDatabase(): void
    {
        $order = $this->createOrderWithPayment('100000009', Order::STATE_PROCESSING);
        $this->createPaymentTransaction($order, 'psp_794');
        $order->setState(Order::STATE_PENDING_PAYMENT);

        $this->assertProcessingStops($this->createProcess()->execute($order, ['transaction_id' => 'psp_794']));
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

        return $this->getObjectManager()->create(
            SkipIfPaymentTransactionExists::class,
            ['logger' => $loggerMock]
        );
    }

    /**
     * Creates an order with a payment method, but without any transactions, to be used in the tests
     *
     * @param string $incrementId
     * @param string $state
     * @return Order
     */
    private function createOrderWithPayment(string $incrementId, string $state = Order::STATE_NEW): Order
    {
        /** @var Order $order */
        $order = $this->getObjectManager()->create(Order::class);

        $order->setIncrementId($incrementId);
        $order->setState($state);
        $order->setStatus($state === Order::STATE_NEW ? 'pending' : $state);
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

        $orderId = (int)$order->getEntityId();
        $this->setOrderStateInDatabase($orderId, $state);

        return $this->getObjectManager()->create(Order::class)->load($orderId);
    }

    /**
     * Writes the order state directly, because saving an order without items lets Magento change the state
     *
     * @param int $orderId
     * @param string $state
     * @return void
     */
    private function setOrderStateInDatabase(int $orderId, string $state): void
    {
        /** @var ResourceConnection $resourceConnection */
        $resourceConnection = $this->getObjectManager()->get(ResourceConnection::class);
        $connection = $resourceConnection->getConnection();
        $table = $resourceConnection->getTableName('sales_order');

        $connection->update(
            $table,
            ['state' => $state, 'status' => $state === Order::STATE_NEW ? 'pending' : $state],
            ['entity_id = ?' => $orderId]
        );

        $storedState = $connection->fetchOne(
            $connection->select()->from($table, ['state'])->where('entity_id = ?', $orderId)
        );
        self::assertSame($state, $storedState);
    }

    /**
     * Creates a payment transaction with the given PSP ID for the payment of the given order, to be used in the tests
     *
     * @param Order $order
     * @param string $pspId
     * @param string $txnType
     * @return void
     * @throws AlreadyExistsException
     */
    private function createPaymentTransaction(
        Order $order,
        string $pspId,
        string $txnType = TransactionInterface::TYPE_CAPTURE
    ): void {
        /** @var Transaction $txn */
        $txn = $this->getObjectManager()->create(Transaction::class);
        $txn->setOrderId((int)$order->getEntityId());
        $txn->setPaymentId((int)$order->getPayment()->getId());
        $txn->setTxnId($pspId);
        $txn->setTxnType($txnType);
        $txn->setIsClosed(1);

        /** @var TransactionResource $txnResource */
        $txnResource = $this->getObjectManager()->get(TransactionResource::class);
        $txnResource->save($txn);
    }

    /**
     * Asserts that the response stops the remaining processes without saving the order
     *
     * @param array $response
     * @return void
     */
    private function assertProcessingStops(array $response): void
    {
        self::assertTrue($response[StatusOperationInterface::SUCCESS_PARAMETER]);
        self::assertTrue($response[ProcessUtil::STOP_PROCESSING]);
        self::assertFalse($response[ProcessInterface::SAVE_ORDER]);
    }

    /**
     * Asserts that the response lets the remaining processes run
     *
     * @param array $response
     * @return void
     */
    private function assertProcessingContinues(array $response): void
    {
        self::assertTrue($response[StatusOperationInterface::SUCCESS_PARAMETER]);
        self::assertArrayNotHasKey(ProcessUtil::STOP_PROCESSING, $response);
    }
}
