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

namespace MultiSafepay\ConnectCore\Test\Integration\Service\Order;

use Exception;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Service\Order\PayMultisafepayOrder;
use MultiSafepay\ConnectCore\Test\Integration\Payment\AbstractTransactionTestCase;
use MultiSafepay\ConnectCore\Util\InvoiceUtil;

class PayMultisafepayOrderTest extends AbstractTransactionTestCase
{
    /**
     * @var PayMultisafepayOrder
     */
    private $payMultisafepayOrderService;

    /**
     * @var array|null
     */
    private $transactionData;

    /**
     * @var InvoiceUtil
     */
    private $invoiceUtil;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->payMultisafepayOrderService = $this->getObjectManager()->create(PayMultisafepayOrder::class);
        $this->transactionData = $this->getManualCaptureTransactionData() ?? [];
        $this->invoiceUtil = $this->getObjectManager()->create(InvoiceUtil::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/multisafepay_visa/payment_action authorize
     * @magentoConfigFixture default_store multisafepay/general/use_manual_capture 1
     * @throws Exception
     */
    public function testTriggerNotificationForManualCaptureCreatedOrder()
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $payment = $order->getPayment();
        $this->payMultisafepayOrderService->execute($order, $payment, $this->transactionData);
        $lastTransaction = $this->getTransactionRepository()->getByTransactionId(
            $payment->getLastTransId(),
            $payment->getEntityId(),
            $order->getEntityId()
        );

        self::assertEquals(Order::STATE_PROCESSING, $order->getState());
        self::assertEquals(TransactionInterface::TYPE_AUTH, $lastTransaction->getTxnType());
        self::assertEquals($this->transactionData['transaction_id'], $lastTransaction->getTxnId());
        self::assertFalse((bool)$lastTransaction->getIsClosed());
        self::assertEquals([], $this->invoiceUtil->getInvoicesByOrderId((string)$order->getId()));
    }
}
