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

namespace MultiSafepay\ConnectCore\Test\Integration\Gateway\Request;

use Exception;
use InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\SalesSequence\Model\Manager;
use Magento\Sales\Exception\CouldNotInvoiceException;
use MultiSafepay\Api\Transactions\CaptureRequest;
use MultiSafepay\Api\Transactions\Transaction as TransactionStatus;
use MultiSafepay\Api\Transactions\Transaction;
use MultiSafepay\ConnectCore\Gateway\Request\Builder\CaptureTransactionBuilder;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Test\Integration\Gateway\AbstractGatewayTestCase;
use MultiSafepay\ConnectCore\Util\AmountUtil;
use MultiSafepay\ConnectCore\Util\CaptureUtil;
use MultiSafepay\ConnectCore\Util\JsonHandler;
use MultiSafepay\ConnectCore\Util\ShipmentUtil;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CaptureTransactionBuilderTest extends AbstractGatewayTestCase
{
    /**
     * @var array
     */
    private $transactionData;

    /**
     * @var Manager
     */
    private $sequenceManager;

    /**
     * @var ShipmentUtil
     */
    private $shipmentUtil;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->transactionData = $this->getManualCaptureTransactionData() ?? [];
        $this->sequenceManager = $this->getObjectManager()->get(Manager::class);
        $this->shipmentUtil = $this->getObjectManager()->get(ShipmentUtil::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/multisafepay_visa/payment_action authorize
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @throws Exception
     */
    public function testBuildPartialCaptureTransactionForFullAmount(): void
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $payment = $order->getPayment();
        $amount = 100;
        $orderIncrementId = $order->getIncrementId();

        foreach ($order->getItems() as $orderItem) {
            $preparedItems[$orderItem->getId()] = $orderItem->getQtyOrdered();
        }

        $captureTransactionBuilder = $this->getCaptureTransactionBuilderMock($orderIncrementId, $this->transactionData);
        $buildSubject = [
            'payment' => $this->getNewPaymentDataObjectFromOrder($order),
            'amount' => $amount,
        ];
        $invoice = $order->prepareInvoice($preparedItems);
        $payment->setInvoice($invoice);
        $captureTransactionData = $captureTransactionBuilder->build($buildSubject);
        $capturePayload = $captureTransactionData['payload'];

        self::assertEquals($orderIncrementId, $captureTransactionData['order_id']);
        self::assertEquals($order->getStoreId(), $captureTransactionData['store_id']);
        self::assertEquals(round($amount * 100, 10), $capturePayload->get('amount'));
        self::assertEquals($invoice->getIncrementId(), $capturePayload->get('invoice_id'));
        self::isNull($capturePayload->get('new_order_id'));
        self::assertEquals(Transaction::COMPLETED, $capturePayload->get('new_order_status'));

        $shipment = $this->createShipmentForOrder($order, $preparedItems);
        $payment->setShipment($shipment);
        $captureTransactionData = $captureTransactionBuilder->build($buildSubject);
        $capturePayload = $captureTransactionData['payload'];

        foreach ($this->shipmentUtil->getShipmentApiRequestData($order, $shipment) as $key => $value) {
            self::assertEquals($value, $capturePayload->get($key));
        }

        self::assertEquals(Transaction::SHIPPED, $capturePayload->get('new_order_status'));
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/multisafepay_visa/payment_action authorize
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @throws Exception
     */
    public function testBuildPartialCaptureTransactionForPartialAmount(): void
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $payment = $order->getPayment();
        $amount = 50;
        $orderIncrementId = $order->getIncrementId();

        foreach ($order->getItems() as $orderItem) {
            $preparedItems[$orderItem->getId()] = 1;
        }

        $captureTransactionBuilder = $this->getCaptureTransactionBuilderMock($orderIncrementId, $this->transactionData);
        $buildSubject = [
            'payment' => $this->getNewPaymentDataObjectFromOrder($order),
            'amount' => $amount,
        ];
        $invoice = $order->prepareInvoice($preparedItems);
        $payment->setInvoice($invoice);
        $captureTransactionData = $captureTransactionBuilder->build($buildSubject);
        $capturePayload = $captureTransactionData['payload'];

        self::assertEquals($orderIncrementId, $captureTransactionData['order_id']);
        self::assertEquals($order->getStoreId(), $captureTransactionData['store_id']);
        self::assertEquals(round($amount * 100, 10), $capturePayload->get('amount'));
        self::assertEquals($invoice->getIncrementId(), $capturePayload->get('invoice_id'));
        self::assertEquals(
            $orderIncrementId . '_' . $capturePayload->get('invoice_id'),
            $capturePayload->get('new_order_id')
        );
        self::assertEquals(Transaction::COMPLETED, $capturePayload->get('new_order_status'));
    }

    /**
     * @throws Exception
     */
    public function testBuildPartialCaptureTransactionForNonManualCaptureOrder(): void
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $orderIncrementId = $order->getIncrementId();
        $buildSubject = [
            'payment' => $this->getNewPaymentDataObjectFromOrder($order),
            'amount' => 50,
        ];
        $transactionData = $this->transactionData;
        unset($transactionData['payment_details']['capture']);
        $transactionData['payment_details'][''] = TransactionStatus::COMPLETED;
        $captureTransactionBuilder = $this->getCaptureTransactionBuilderMock($orderIncrementId, $transactionData);

        $this->expectExceptionMessage(
            'Manual MultiSafepay online capture can\'t be processed for non manual capture orders'
        );
        $this->expectException(CouldNotInvoiceException::class);

        $captureTransactionBuilder->build($buildSubject);
    }

    /**
     * @throws Exception
     */
    public function testBuildPartialCaptureTransactionForExpriredManualCaptureTransaction(): void
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $orderIncrementId = $order->getIncrementId();
        $buildSubject = [
            'payment' => $this->getNewPaymentDataObjectFromOrder($order),
            'amount' => 50,
        ];
        $transactionData = $this->transactionData;
        $transactionData['payment_details']['capture_expiry'] = '2000-01-01T11:42:00';
        $captureTransactionBuilder = $this->getCaptureTransactionBuilderMock($orderIncrementId, $transactionData);

        $this->expectExceptionMessage(
            'Reservation has been expired for current online capture.'
        );
        $this->expectException(CouldNotInvoiceException::class);

        $captureTransactionBuilder->build($buildSubject);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/multisafepay_visa/payment_action authorize
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @dataProvider         amountVariationsDataProvider
     *
     * @param mixed $amount
     * @param bool $expected
     * @param array $exceptionData
     * @throws LocalizedException
     */
    public function testBuildPartialCaptureTransactionWithWrongAmounts(
        $amount,
        bool $expected,
        array $exceptionData = []
    ): void {
        $order = $this->getOrderWithVisaPaymentMethod();
        $orderIncrementId = $order->getIncrementId();
        $captureTransactionBuilder = $this->getCaptureTransactionBuilderMock($orderIncrementId, $this->transactionData);
        $buildSubject = [
            'payment' => $this->getNewPaymentDataObjectFromOrder($order),
            'amount' => $amount,
        ];

        if ($expected) {
            $preparedItems = [];
            foreach ($order->getItems() as $orderItem) {
                $preparedItems[$orderItem->getId()] = $orderItem->getQtyOrdered();
            }

            $order->getPayment()->setInvoice($order->prepareInvoice($preparedItems));
            $captureTransactionData = $captureTransactionBuilder->build($buildSubject);

            self::assertEquals(
                round((float)$amount * 100, 10),
                $captureTransactionData['payload']->get('amount')
            );
        } else {
            $this->expectExceptionMessage($exceptionData['exceptionMessage']);
            $this->expectException($exceptionData['exception']);

            $captureTransactionBuilder->build($buildSubject);
        }
    }

    /**
     * @return array
     */
    public function amountVariationsDataProvider(): array
    {
        return [
            [
                0,
                false,
                [
                    'exception' => CouldNotInvoiceException::class,
                    'exceptionMessage' => 'Invoices with 0 or negative amount can not be processed.'
                                          . ' Please set a different amount',
                ],
            ],
            [
                110,
                false,
                [
                    'exception' => CouldNotInvoiceException::class,
                    'exceptionMessage' => 'Manual payment capture amount is can\'t be processed,  please try again.',
                ],
            ],
            [
                null,
                false,
                [
                    'exception' => InvalidArgumentException::class,
                    'exceptionMessage' => 'Amount should be provided',
                ],
            ],
            [
                '50',
                true,
                [],
            ],
        ];
    }

    /**
     * @param string $orderId
     * @param array $transactionData
     * @return MockObject
     */
    private function getCaptureTransactionBuilderMock(
        string $orderId,
        array $transactionData
    ): MockObject {
        return $this->getMockBuilder(CaptureTransactionBuilder::class)
            ->setConstructorArgs([
                $this->getObjectManager()->get(AmountUtil::class),
                $this->getObjectManager()->get(CaptureUtil::class),
                $this->setupSdkFactory($this->getSdkMockWithPartialCapture($orderId, $transactionData)),
                $this->getObjectManager()->get(CaptureRequest::class),
                $this->shipmentUtil,
                $this->sequenceManager,
                $this->getObjectManager()->get(Logger::class),
                $this->getObjectManager()->get(JsonHandler::class)
            ])
            ->setMethodsExcept(['build'])
            ->getMock();
    }
}
