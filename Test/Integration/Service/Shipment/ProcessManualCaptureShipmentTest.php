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

namespace MultiSafepay\ConnectCore\Test\Integration\Service\Shipment;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Exception\CouldNotInvoiceException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\OrderRepository;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\EmailSender;
use MultiSafepay\ConnectCore\Service\Invoice\CreateInvoiceAfterShipment;
use MultiSafepay\ConnectCore\Service\Shipment\AddShippingToTransaction;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\InvoiceUtil;
use MultiSafepay\ConnectCore\Util\ShipmentUtil;
use MultiSafepay\ConnectCore\Service\Shipment\ProcessManualCaptureShipment;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProcessManualCaptureShipmentTest extends AbstractTestCase
{
    private $createInvoiceAfterShipment;
    private $processManualCaptureShipment;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $logger = $this->getObjectManager()->get(Logger::class);
        $shipmentUtil = $this->getObjectManager()->get(ShipmentUtil::class);
        $messageManager = $this->getObjectManager()->get(ManagerInterface::class);
        $orderRepository = $this->getObjectManager()->get(OrderRepository::class);
        $this->createInvoiceAfterShipment = $this->createMock(CreateInvoiceAfterShipment::class);
        $emailSender = $this->getObjectManager()->get(EmailSender::class);
        $invoiceUtil = $this->createMock(InvoiceUtil::class);
        $addShippingToTransaction = $this->getObjectManager()->get(AddShippingToTransaction::class);

        $this->processManualCaptureShipment = new ProcessManualCaptureShipment(
            $logger,
            $shipmentUtil,
            $messageManager,
            $orderRepository,
            $this->createInvoiceAfterShipment,
            $emailSender,
            $invoiceUtil,
            $addShippingToTransaction
        );
    }

    /**
     * Test if the execute function processes a manual capture shipment correctly
     *
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/multisafepay_visa/payment_action authorize
     * @magentoConfigFixture default_store payment/multisafepay_visa/manual_capture 1
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     *
     * @throws CouldNotInvoiceException
     * @throws LocalizedException
     */
    public function testExecuteCreatesInvoiceAfterShipment()
    {
        $shipment = $this->createMock(Shipment::class);
        $order = $this->getOrder();
        $payment = $this->createMock(Payment::class);

        $this->createInvoiceAfterShipment->expects($this->once())
            ->method('execute')
            ->with($order, $shipment, $payment)
            ->willReturn(true);

        $this->processManualCaptureShipment->execute($shipment, $order, $payment);
    }

    /**
     * Test if the execute function throws an exception when it cannot create an invoice
     *
     * @return void
     * @throws CouldNotInvoiceException
     */
    public function testExecuteThrowsExceptionWhenCannotCreateInvoice()
    {
        $this->expectException(CouldNotInvoiceException::class);

        $shipment = $this->createMock(Shipment::class);
        $order = $this->createMock(Order::class);
        $payment = $this->createMock(Payment::class);

        $this->createInvoiceAfterShipment->expects($this->once())
            ->method('execute')
            ->with($order, $shipment, $payment)
            ->willThrowException(new CouldNotInvoiceException(__('Could not create invoice after shipment')));

        $this->processManualCaptureShipment->execute($shipment, $order, $payment);
    }

    /**
     * Test if the sendInvoiceEmail function throws an exception when it cannot send an email
     *
     * @return void
     * @throws Exception
     */
    public function testSendInvoiceEmailThrowsExceptionWhenCannotSendEmail()
    {
        $this->expectException(MailException::class);

        $payment = $this->createMock(Payment::class);
        $invoice = $this->createMock(Invoice::class);
        $service = $this->getMockBuilder(ProcessManualCaptureShipment::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['sendInvoiceEmail'])
            ->getMock();

        $service->expects($this->once())
            ->method('sendInvoiceEmail')
            ->with($payment, $invoice)
            ->willThrowException(new MailException(__('Could not send invoice email')));

        $service->sendInvoiceEmail($payment, $invoice);
    }
}
