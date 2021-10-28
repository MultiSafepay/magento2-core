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

namespace MultiSafepay\ConnectCore\Test\Integration\Service\Shipment;

use Exception;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\EmailSender;
use MultiSafepay\ConnectCore\Service\Shipment\AddShippingToTransaction;
use MultiSafepay\ConnectCore\Service\Shipment\ProcessManualCaptureShipment;
use MultiSafepay\ConnectCore\Test\Integration\Payment\AbstractTransactionTestCase;
use MultiSafepay\ConnectCore\Util\InvoiceUtil;
use MultiSafepay\ConnectCore\Util\ShipmentUtil;
use PHPUnit\Framework\MockObject\MockObject;
use MultiSafepay\ConnectCore\Service\Invoice\CreateInvoiceAfterShipment;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProcessManualCaptureShipmentTest extends AbstractTransactionTestCase
{
    /**
     * @var array|null
     */
    private $transactionData;

    /**
     * @var InvoiceUtil
     */
    private $invoiceUtil;

    /**
     * @var ShipmentUtil
     */
    private $shipmentUtil;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->transactionData = $this->getManualCaptureTransactionData() ?? [];
        $this->invoiceUtil = $this->getObjectManager()->create(InvoiceUtil::class);
        $this->shipmentUtil = $this->getObjectManager()->get(ShipmentUtil::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order_with_two_order_items_with_simple_product.php
     * @magentoConfigFixture default_store payment/multisafepay_visa/payment_action authorize
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @magentoConfigFixture default_store multisafepay/general/use_manual_capture 1
     * @throws Exception
     */
    public function testCreateFullShipmentForManualCaptureCreatedOrder()
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $payment = $order->getPayment();
        $shipmentItems = [];

        foreach ($order->getItems() as $orderItem) {
            $shipmentItems[$orderItem->getId()] = $orderItem->getQtyOrdered();
        }

        $shipment = $this->createShipmentForOrder($order, $shipmentItems);
        $processManualCaptureShipmentService = $this->getMockBuilder(ProcessManualCaptureShipment::class)
            ->setConstructorArgs([
                $this->getObjectManager()->get(Logger::class),
                $this->shipmentUtil,
                $this->getObjectManager()->get(ManagerInterface::class),
                $this->getObjectManager()->get(OrderRepositoryInterface::class),
                $this->getCreateInvoiceAfterShipmentMock($order, $shipment, $payment),
                $this->getObjectManager()->get(EmailSender::class),
                $this->getObjectManager()->get(InvoiceUtil::class),
                $this->getAddShippingToTransactionMock($order, $shipment),
            ])
            ->setMethodsExcept(['execute'])
            ->getMock();

        $processManualCaptureShipmentService->execute($shipment, $order, $payment);

        self::assertFalse($this->shipmentUtil->isOrderShippedPartially($order));
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order_with_two_order_items_with_simple_product.php
     * @magentoConfigFixture default_store payment/multisafepay_visa/payment_action authorize
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @magentoConfigFixture default_store multisafepay/general/use_manual_capture 1
     * @throws Exception
     */
    public function testCreatePartialShipmentForManualCaptureCreatedOrder()
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $payment = $order->getPayment();
        $orderItems = $order->getItems();
        $firstOrderItem = reset($orderItems);
        $shipmentItems[$firstOrderItem->getId()] = $firstOrderItem->getQtyOrdered();
        $shipment = $this->createShipmentForOrder($order, $shipmentItems);
        $processManualCaptureShipmentService = $this->getMockBuilder(ProcessManualCaptureShipment::class)
            ->setConstructorArgs([
                $this->getObjectManager()->get(Logger::class),
                $this->shipmentUtil,
                $this->getObjectManager()->get(ManagerInterface::class),
                $this->getObjectManager()->get(OrderRepositoryInterface::class),
                $this->getCreateInvoiceAfterShipmentMock($order, $shipment, $payment),
                $this->getObjectManager()->get(EmailSender::class),
                $this->getObjectManager()->get(InvoiceUtil::class),
                $this->getAddShippingToTransactionMock($order, $shipment),
            ])
            ->setMethodsExcept(['execute'])
            ->getMock();

        $processManualCaptureShipmentService->execute($shipment, $order, $payment);

        self::assertTrue($this->shipmentUtil->isOrderShippedPartially($order));

        $shipmentItems = [];
        $lastOrderItem = end($orderItems);
        $shipmentItems[$lastOrderItem->getId()] = $lastOrderItem->getQtyOrdered();
        $shipment = $this->createShipmentForOrder($order, $shipmentItems);
        $processManualCaptureShipmentService->execute($shipment, $order, $payment);

        self::assertTrue($this->shipmentUtil->isOrderShippedPartially($order));
    }

    /**
     * @param OrderInterface $order
     * @param ShipmentInterface $shipment
     * @param OrderPaymentInterface $orderPayment
     * @return MockObject
     */
    private function getCreateInvoiceAfterShipmentMock(
        OrderInterface $order,
        ShipmentInterface $shipment,
        OrderPaymentInterface $orderPayment
    ): MockObject {
        $createInvoiceAfterShipmentMock = $this->getMockBuilder(CreateInvoiceAfterShipment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $createInvoiceAfterShipmentMock->expects(self::any())
            ->method('execute')
            ->with($order, $shipment, $orderPayment)
            ->willReturn(true);

        return $createInvoiceAfterShipmentMock;
    }

    /**
     * @param OrderInterface $order
     * @param ShipmentInterface $shipment
     * @return MockObject
     */
    private function getAddShippingToTransactionMock(
        OrderInterface $order,
        ShipmentInterface $shipment
    ): MockObject {
        $addShippingToTransactionMock = $this->getMockBuilder(AddShippingToTransaction::class)
            ->disableOriginalConstructor()
            ->getMock();

        $addShippingToTransactionMock->expects(self::any())
            ->method('execute')
            ->with($shipment, $order);

        return $addShippingToTransactionMock;
    }
}
