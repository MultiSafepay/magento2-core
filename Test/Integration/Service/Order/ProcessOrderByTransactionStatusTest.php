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
 * Copyright © 2022 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Test\Integration\Service\Order;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use MultiSafepay\Api\Base\Response;
use MultiSafepay\Api\TransactionManager;
use MultiSafepay\Api\Transactions\Transaction as TransactionStatus;
use MultiSafepay\Api\Transactions\UpdateRequest;
use MultiSafepay\ConnectCore\Service\Order\ProcessOrderByTransactionStatus;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\InvoiceUtil;
use MultiSafepay\ConnectCore\Util\OrderStatusUtil;
use Psr\Http\Client\ClientExceptionInterface;

class ProcessOrderByTransactionStatusTest extends AbstractTestCase
{
    /**
     * @var ProcessOrderByTransactionStatus
     */
    private $processOrderByTransactionStatus;

    /**
     * @var array
     */
    private $transactionData;

    /**
     * @var InvoiceUtil
     */
    private $invoiceUtil;

    /**
     * @var OrderStatusUtil
     */
    private $orderStatusUtil;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->processOrderByTransactionStatus = $this->getObjectManager()->get(ProcessOrderByTransactionStatus::class);
        $this->transactionData = $this->getManualCaptureTransactionData() ?? [];
        $this->invoiceUtil = $this->getObjectManager()->get(InvoiceUtil::class);
        $this->orderStatusUtil = $this->getObjectManager()->get(OrderStatusUtil::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @throws LocalizedException
     * @throws ClientExceptionInterface
     */
    public function testProcessOrderByTransactionStatusCompleteAndShipped(): void
    {
        $this->getProcessOrderByTransactionStatusTest(TransactionStatus::COMPLETED);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @throws LocalizedException
     * @throws ClientExceptionInterface
     */
    public function testProcessOrderByTransactionStatusUncleared(): void
    {
        $this->getProcessOrderByTransactionStatusTest(TransactionStatus::UNCLEARED);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order_new.php
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @throws LocalizedException
     * @throws ClientExceptionInterface
     */
    public function testProcessOrderByTransactionStatusExpired(): void
    {
        $this->getProcessOrderByTransactionStatusTest(TransactionStatus::EXPIRED);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @throws LocalizedException
     * @throws ClientExceptionInterface
     */
    public function testProcessOrderByTransactionStatusCancelled(): void
    {
        $this->getProcessOrderByTransactionStatusTest(TransactionStatus::CANCELLED);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/status/reserved_status pending
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @throws LocalizedException
     * @throws ClientExceptionInterface
     */
    public function testProcessOrderByTransactionStatusReserved(): void
    {
        $this->getProcessOrderByTransactionStatusTest(TransactionStatus::RESERVED);
    }

    /**
     * @param string $transactionStatus
     * @throws ClientExceptionInterface
     * @throws LocalizedException
     */
    private function getProcessOrderByTransactionStatusTest(string $transactionStatus): void
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $payment = $order->getPayment();
        $this->transactionData['financial_status'] = $transactionStatus;
        $this->processOrderByTransactionStatus->execute(
            $order,
            $payment,
            $this->getTransactionManagerMock($order->getIncrementId()),
            $this->transactionData,
            $transactionStatus,
            $payment->getMethodInstance()->getConfigData('gateway_code')
        );
        $statusHistories = $order->getStatusHistories();
        $lastComment = end($statusHistories);

        switch ($transactionStatus) {
            case TransactionStatus::RESERVED:
                self::assertEquals(
                    $this->orderStatusUtil->getOrderStatusByTransactionStatus($order, $transactionStatus),
                    $order->getStatus()
                );
                self::assertEquals(
                    'Order status has been changed to: pending',
                    $lastComment->getComment()->render()
                );

                break;
            case TransactionStatus::UNCLEARED:
                self::assertEquals(
                    'Uncleared Transaction. You can accept the transaction manually in MultiSafepay Control',
                    $lastComment->getComment()->render()
                );

                break;
            case TransactionStatus::COMPLETED:
                $invoice = $this->invoiceUtil->getLastCreatedInvoiceByOrderId($order->getId());
                self::assertEquals(Order::STATE_PROCESSING, $order->getState());
                self::assertEquals($this->orderStatusUtil->getProcessingStatus($order), $order->getStatus());
                self::assertEquals((string)$payment->getTransactionId(), (string)$invoice->getTransactionId());
                self::assertTrue((bool)$invoice->getEmailSent());
                self::assertTrue($payment->getIsTransactionApproved());
                self::assertEquals((float)$order->getBaseGrandTotal(), (float)$payment->getAmountPaid());

                break;
            case TransactionStatus::EXPIRED:
            case TransactionStatus::CANCELLED:
                self::assertEquals(
                    'MultiSafepay Transaction status: ' . $transactionStatus,
                    $lastComment->getComment()
                );
                self::assertEquals(Order::STATE_CANCELED, $order->getState());
                self::assertEquals(Order::STATE_CANCELED, $order->getStatus());

                break;
        }
    }

    /**
     * @param string $orderId
     * @return TransactionManager
     */
    private function getTransactionManagerMock(string $orderId): TransactionManager
    {
        $transactionManagerMock = $this->getMockBuilder(TransactionManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockResponse = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockResponse->method('getResponseData')
            ->willReturn([]);

        $transactionManagerMock->method('update')
            ->with($orderId, $this->getObjectManager()->get(UpdateRequest::class))
            ->willReturn($mockResponse);

        return $transactionManagerMock;
    }
}
