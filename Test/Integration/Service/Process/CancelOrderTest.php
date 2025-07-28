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

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Service\Process\CancelOrder;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class CancelOrderTest extends AbstractTestCase
{
    /**
     * @var CancelOrder
     */
    private $cancelOrder;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->cancelOrder = $this->getObjectManager()->create(CancelOrder::class);
    }

    /**
     * Test that the cancelOrder method cancels an order and returns success status.
     *
     * @magentoDataFixture   Magento/Sales/_files/order.php
     *
     * @return void
     * @throws LocalizedException
     * @throws Exception
     */
    public function testCancelOrder(): void
    {
        /** @var Order $order */
        $order = $this->getOrder();
        $result = $this->cancelOrder->execute($order, $this->getTransactionData());

        self::assertSame([StatusOperationInterface::SUCCESS_PARAMETER => true], $result);

        // Refresh the order
        $order = $this->getOrder();

        self::assertSame(Order::STATE_CANCELED, $order->getState());
    }

    /**
     * Test that cancelOrder method adds a comment to the order's status history
     *
     * @magentoDataFixture   Magento/Sales/_files/order.php
     *
     * @return void
     * @throws LocalizedException
     * @throws Exception
     */
    public function testCancelOrderAddsCommentToStatusHistory(): void
    {
        /** @var Order $order */
        $order = $this->getOrder();

        $transactionData = [
            'status' => 'cancelled',
            'transaction_id' => 'MSP_TEST_123456'
        ];

        // Get initial comment count
        $initialCommentCount = count($order->getStatusHistoryCollection());

        $result = $this->cancelOrder->execute($order, $transactionData);

        self::assertSame([StatusOperationInterface::SUCCESS_PARAMETER => true], $result);

        // Refresh the order to get updated data
        $order = $this->getOrder();

        // Verify comment was added
        $statusHistory = $order->getStatusHistoryCollection();
        self::assertGreaterThan($initialCommentCount, count($statusHistory));

        // Get the last comment
        $lastComment = $statusHistory->getLastItem();
        $commentText = $lastComment->getComment();

        // Verify comment contains expected information
        self::assertStringContainsString('Order canceled by MultiSafepay', $commentText);
        self::assertStringContainsString('Transaction status: cancelled', $commentText);
        self::assertStringContainsString('Transaction ID: MSP_TEST_123456', $commentText);
    }

    /**
     * Test that cancelOrder method handles empty transaction data gracefully
     *
     * @magentoDataFixture   Magento/Sales/_files/order.php
     *
     * @return void
     * @throws LocalizedException
     * @throws Exception
     */
    public function testCancelOrderAddsCommentWithUnknownTransactionData(): void
    {
        /** @var Order $order */
        $order = $this->getOrder();

        $transactionData = []; // Empty transaction data

        $result = $this->cancelOrder->execute($order, $transactionData);

        self::assertSame([StatusOperationInterface::SUCCESS_PARAMETER => true], $result);

        // Refresh the order
        $order = $this->getOrder();

        // Get the last comment
        $statusHistory = $order->getStatusHistoryCollection();
        $lastComment = $statusHistory->getLastItem();
        $commentText = $lastComment->getComment();

        // Verify comment contains default values when transaction data is missing
        self::assertStringContainsString('Order canceled by MultiSafepay', $commentText);
        self::assertStringContainsString('Transaction status: unknown', $commentText);
        self::assertStringContainsString('Transaction ID: unknown', $commentText);
    }
}
