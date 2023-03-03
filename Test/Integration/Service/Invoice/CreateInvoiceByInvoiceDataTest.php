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

namespace MultiSafepay\ConnectCore\Test\Integration\Service\Invoice;

use Exception;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\ConnectCore\Service\Invoice\CreateInvoiceByInvoiceData;
use MultiSafepay\ConnectCore\Test\Integration\Payment\AbstractPaymentTestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreateInvoiceByInvoiceDataTest extends AbstractPaymentTestCase
{
    /**
     * @var CreateInvoiceByInvoiceData
     */
    private $createInvoiceByInvoiceData;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->createInvoiceByInvoiceData = $this->getObjectManager()->get(CreateInvoiceByInvoiceData::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/multisafepay_visa/payment_action authorize
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @magentoConfigFixture default_store multisafepay/general/use_manual_capture 1
     * @throws Exception
     */
    public function testCreateInvoiceByInvoiceData()
    {
        $order = $this->getOrderWithVisaPaymentMethod();

        foreach ($order->getItems() as $orderItem) {
            $invoiceData[$orderItem->getId()] = $orderItem->getQtyOrdered();

            break;
        }

        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock->expects(self::once())
            ->method('capture')
            ->willReturn(null);

        $paymentMock->expects(self::once())
            ->method('getOrder')
            ->willReturn($order);

        $invoice = $this->createInvoiceByInvoiceData->execute($order, $paymentMock, $invoiceData);

        /**
         * make fake payment of an order
         */
        $invoice->pay();

        self::assertTrue($order->getIsInProcess());
        self::assertEquals($order->getBaseTotalPaid(), $invoice->getBaseGrandTotal());
        self::assertEquals(Order::STATE_PROCESSING, $order->getState());
        self::assertEquals(
            $order->getInvoiceCollection()->getFirstItem()->getIncrementId(),
            $invoice->getIncrementId()
        );
    }
}
