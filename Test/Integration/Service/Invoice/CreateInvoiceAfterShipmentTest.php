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

namespace MultiSafepay\ConnectCore\Test\Integration\Service\Invoice;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\IdealConfigProvider;
use MultiSafepay\ConnectCore\Service\Invoice\CreateInvoiceAfterShipment;
use MultiSafepay\ConnectCore\Service\Invoice\CreateInvoiceByInvoiceData;
use MultiSafepay\ConnectCore\Test\Integration\Payment\AbstractPaymentTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionObject;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreateInvoiceAfterShipmentTest extends AbstractPaymentTestCase
{
    /**
     * @var CreateInvoiceAfterShipment
     */
    private $createInvoiceAfterShipment;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->createInvoiceAfterShipment = $this->getObjectManager()->get(CreateInvoiceAfterShipment::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/multisafepay_visa/payment_action authorize
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @magentoConfigFixture default_store multisafepay/general/use_manual_capture 1
     * @throws Exception
     */
    public function testCreateInvoiceAfterShipment()
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $payment = $order->getPayment();
        $shipmentItems = [];

        foreach ($order->getItems() as $orderItem) {
            $shipmentItems[$orderItem->getId()] = $orderItem->getQtyOrdered();
        }

        $shipment = $this->createShipmentForOrder($order, $shipmentItems);
        $createInvoiceAfterShipmentService = $this->getMockBuilder(CreateInvoiceAfterShipment::class)
            ->setConstructorArgs([
                $this->getObjectManager()->get(Logger::class),
                $this->getObjectManager()->get(OrderItemRepositoryInterface::class),
                $this->getCreateInvoiceByInvoiceDataMock($order),
            ])
            ->setMethodsExcept(['execute'])
            ->getMock();

        self::assertTrue($createInvoiceAfterShipmentService->execute($order, $shipment, $payment));

        $reflector = new ReflectionObject($this->createInvoiceAfterShipment);
        $method = $reflector->getMethod('getInvoiceItemsQtyDataFromShipment');
        $method->setAccessible(true);
        $shipmentItem = $shipment->getItems()[0];

        self::assertEquals(
            [$shipmentItem->getOrderItemId() => $shipmentItem->getQty()],
            $method->invoke($this->createInvoiceAfterShipment, $shipment)
        );

        $this->expectExceptionMessage("Invoice can't be captured");
        $this->expectException(LocalizedException::class);

        $createInvoiceAfterShipmentService->execute(
            $order,
            $shipment,
            $this->getPayment(IdealConfigProvider::CODE, 'direct')
        );
    }

    /**
     * @param OrderInterface $order
     * @return MockObject
     */
    private function getCreateInvoiceByInvoiceDataMock(
        OrderInterface $order
    ): MockObject {
        $invoice = $this->getObjectManager()->create(InvoiceInterface::class);
        $invoice->setIncrementId($order->getIncrementId());
        $createInvoiceByInvoiceDataMock = $this->getMockBuilder(CreateInvoiceByInvoiceData::class)
            ->disableOriginalConstructor()
            ->getMock();

        $createInvoiceByInvoiceDataMock->expects(self::any())
            ->method('execute')
            ->willReturn($invoice);

        return $createInvoiceByInvoiceDataMock;
    }
}
