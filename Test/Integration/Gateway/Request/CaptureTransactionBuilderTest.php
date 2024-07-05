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

namespace MultiSafepay\ConnectCore\Test\Integration\Gateway\Request;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Exception\CouldNotInvoiceException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection;
use Magento\Store\Model\Store;
use MultiSafepay\Api\TransactionManager;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\Sdk;
use MultiSafepay\ConnectCore\Gateway\Request\Builder\CaptureTransactionBuilder;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\AmountUtil;
use MultiSafepay\ConnectCore\Util\CaptureUtil;
use MultiSafepay\ConnectCore\Util\JsonHandler;
use MultiSafepay\ConnectCore\Util\ShipmentUtil;
use MultiSafepay\Api\Transactions\CaptureRequest;
use Magento\SalesSequence\Model\Manager;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CaptureTransactionBuilderTest extends AbstractTestCase
{
    /**
     * @var CaptureTransactionBuilder
     */
    private $captureTransactionBuilder;

    /**
     * @var SdkFactory
     */
    private $sdkFactory;

    /**
     * @var CaptureUtil
     */
    private $captureUtil;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $amountUtil = $this->createMock(AmountUtil::class);
        $this->captureUtil = $this->createMock(CaptureUtil::class);
        $this->sdkFactory = $this->createMock(SdkFactory::class);
        $captureRequest = $this->createMock(CaptureRequest::class);
        $shipmentUtil = $this->createMock(ShipmentUtil::class);
        $sequenceManager = $this->createMock(Manager::class);
        $logger = $this->createMock(Logger::class);
        $jsonHandler = $this->createMock(JsonHandler::class);

        $this->captureTransactionBuilder = new CaptureTransactionBuilder(
            $amountUtil,
            $this->captureUtil,
            $this->sdkFactory,
            $captureRequest,
            $shipmentUtil,
            $sequenceManager,
            $logger,
            $jsonHandler
        );
    }

    /**
     * Test if the build method will return the expected data
     *
     * @return void
     * @throws LocalizedException
     * @throws CouldNotInvoiceException
     * @throws Exception
     */
    public function testBuildWillReturnExpectedData()
    {
        $buildSubject = $this->prepareTestData();
        $sdk = $this->createMock(Sdk::class);
        $transaction = $this->createMock(TransactionResponse::class);

        $this->sdkFactory->method('create')->willReturn($sdk);
        $transactionManager = $this->createMock(TransactionManager::class);

        $sdk->method('getTransactionManager')->willReturn($transactionManager);
        $transactionManager->method('get')->willReturn($transaction);
        $transaction->method('getData')->willReturn($this->getManualCaptureTransactionData());
        $this->captureUtil->method('isCaptureManualTransaction')->willReturn(true);
        $this->captureUtil->method('isManualCapturePossibleForAmount')->willReturn(true);

        $invoice = $this->createMock(Invoice::class);
        $invoice->method('getIncrementId')->willReturn('10000001');
        $invoice->method('getData')->willReturn(['some_data' => 'some_value']);

        $invoiceCollection = $this->createMock(Collection::class);
        $invoiceCollection->method('getLastItem')->willReturn($invoice);

        $order = $buildSubject['payment']->getPayment()->getOrder();
        $order->method('getInvoiceCollection')->willReturn($invoiceCollection);

        $result = $this->captureTransactionBuilder->build($buildSubject);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('payload', $result);
        $this->assertArrayHasKey('order_id', $result);
        $this->assertSame('10000001', $result['order_id']);
        $this->assertArrayHasKey(Store::STORE_ID, $result);
        $this->assertSame(1, $result[Store::STORE_ID]);
    }

    /**
     * Test if the build method will return the expected data
     *
     * @return void
     * @throws LocalizedException
     * @throws CouldNotInvoiceException
     * @throws Exception
     */
    public function testBuildWillThrowInvoiceExceptionWhenTransactionManagerReturnsException()
    {
        $buildSubject = $this->prepareTestData();
        $sdk = $this->createMock(Sdk::class);
        $this->sdkFactory->method('create')->willReturn($sdk);

        $this->expectException(CouldNotInvoiceException::class);
        $this->captureTransactionBuilder->build($buildSubject);
    }

    /**
     * Test if the build method will return the expected data
     *
     * @return void
     * @throws LocalizedException
     * @throws CouldNotInvoiceException
     * @throws Exception
     */
    public function testBuildWillThrowInvoiceExceptionWhenTransactionIsNotManualCapture()
    {
        $buildSubject = $this->prepareTestData();
        $sdk = $this->createMock(Sdk::class);
        $transaction = $this->createMock(TransactionResponse::class);

        $this->sdkFactory->method('create')->willReturn($sdk);
        $transactionManager = $this->createMock(TransactionManager::class);

        $sdk->method('getTransactionManager')->willReturn($transactionManager);
        $transactionManager->method('get')->willReturn($transaction);
        $transaction->method('getData')->willReturn($this->getManualCaptureTransactionData());
        $this->captureUtil->method('isCaptureManualTransaction')->willReturn(false);

        $this->expectException(CouldNotInvoiceException::class);
        $this->expectExceptionMessage(
            'Manual MultiSafepay online capture can\'t be processed for non manual capture orders'
        );

        $this->captureTransactionBuilder->build($buildSubject);
    }

    /**
     * Test if the build method will return the expected data
     *
     * @return void
     * @throws LocalizedException
     * @throws CouldNotInvoiceException
     * @throws Exception
     */
    public function testBuildWillThrowInvoiceExceptionWhenManualCaptureReservationHasExpired()
    {
        $buildSubject = $this->prepareTestData();
        $sdk = $this->createMock(Sdk::class);
        $transaction = $this->createMock(TransactionResponse::class);

        $this->sdkFactory->method('create')->willReturn($sdk);
        $transactionManager = $this->createMock(TransactionManager::class);

        $sdk->method('getTransactionManager')->willReturn($transactionManager);
        $transactionManager->method('get')->willReturn($transaction);
        $transaction->method('getData')->willReturn($this->getManualCaptureTransactionData());
        $this->captureUtil->method('isCaptureManualTransaction')->willReturn(true);
        $this->captureUtil->method('isCaptureManualReservationExpired')->willReturn(true);

        $this->expectException(CouldNotInvoiceException::class);
        $this->expectExceptionMessage('Reservation has been expired for current online capture.');

        $this->captureTransactionBuilder->build($buildSubject);
    }

    /**
     * Test if the build method will return the expected data
     *
     * @return void
     * @throws LocalizedException
     * @throws CouldNotInvoiceException
     * @throws Exception
     */
    public function testBuildWillReturnExpectedDataWillThrowExceptionWithWrongAmount()
    {
        $buildSubject = $this->prepareTestData();
        $sdk = $this->createMock(Sdk::class);
        $transaction = $this->createMock(TransactionResponse::class);

        $this->sdkFactory->method('create')->willReturn($sdk);
        $transactionManager = $this->createMock(TransactionManager::class);

        $sdk->method('getTransactionManager')->willReturn($transactionManager);
        $transactionManager->method('get')->willReturn($transaction);
        $transaction->method('getData')->willReturn($this->getManualCaptureTransactionData());
        $this->captureUtil->method('isCaptureManualTransaction')->willReturn(true);
        $this->captureUtil->method('isManualCapturePossibleForAmount')->willReturn(false);

        $this->expectException(CouldNotInvoiceException::class);
        $this->expectExceptionMessage('Manual payment capture amount can\'t be processed,  please try again.');

        $this->captureTransactionBuilder->build($buildSubject);
    }

    /**
     * @return array
     */
    public function prepareTestData(): array
    {
        $buildSubject = [
            'payment' => $this->createMock(PaymentDataObjectInterface::class),
            'amount' => 100.0
        ];

        $payment = $this->createMock(Payment::class);
        $order = $this->createMock(Order::class);

        $buildSubject['payment']->method('getPayment')->willReturn($payment);
        $payment->method('getOrder')->willReturn($order);
        $order->method('getIncrementId')->willReturn('10000001');
        $order->method('getStoreId')->willReturn('1');

        return $buildSubject;
    }
}
