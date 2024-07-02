<?php declare(strict_types=1);

namespace MultiSafepay\Test\Integration\Util;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\CreditCardConfigProvider;
use MultiSafepay\ConnectCore\Observer\Gateway\CreditCardDataAssignObserver;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\CaptureUtil;

class CaptureUtilTest extends AbstractTestCase
{
    /**
     * @var CaptureUtil
     */
    private $captureUtil;

    /**
     * @var array|null
     */
    private $transactionData;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->captureUtil = $this->getObjectManager()->create(CaptureUtil::class);
        $this->transactionData = $this->getManualCaptureTransactionData();
    }

    /**
     * Test if manual capture is possible for provided amount
     *
     * @return void
     */
    public function testIsManualCapturePossibleForAmount(): void
    {
        self::assertTrue($this->captureUtil->isManualCapturePossibleForAmount(
            $this->transactionData,
            100
        ));

        self::assertTrue($this->captureUtil->isManualCapturePossibleForAmount(
            $this->transactionData,
            10.1
        ));

        self::assertFalse($this->captureUtil->isManualCapturePossibleForAmount(
            $this->transactionData,
            105
        ));
    }

    /**
     * Test if the transaction is a manual capture transaction
     *
     * @return void
     */
    public function testIsCaptureManualTransaction(): void
    {
        self::assertTrue($this->captureUtil->isCaptureManualTransaction(
            $this->transactionData
        ));
    }

    /**
     * Test if the manual capture reservation has been expired
     *
     * @return void
     */
    public function testIsCaptureManualReservationExpired(): void
    {
        $transactionData = $this->transactionData;

        self::assertFalse($this->captureUtil->isCaptureManualReservationExpired($transactionData));

        $transactionData['payment_details']['capture_expiry'] = '2000-01-01T11:42:00';

        self::assertTrue($this->captureUtil->isCaptureManualReservationExpired($transactionData));
    }

    /**
     * Test if manual capture is enabled for the payment method
     *
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/multisafepay_visa/payment_action authorize
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @magentoConfigFixture default_store payment/multisafepay_visa/manual_capture 1
     *
     * @throws LocalizedException
     */
    public function testIsManualCaptureEnabled(): void
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $payment = $order->getPayment();

        self::assertTrue($this->captureUtil->isManualCaptureEnabled($payment));

        $payment->setMethod(CreditCardConfigProvider::CODE);
        $payment->setAdditionalInformation(
            CreditCardDataAssignObserver::CREDIT_CARD_BRAND_PARAM_NAME,
            'VISA'
        );

        self::assertTrue($this->captureUtil->isManualCaptureEnabled($payment));

        $payment->setAdditionalInformation(
            CreditCardDataAssignObserver::CREDIT_CARD_BRAND_PARAM_NAME,
            'AMEX'
        );
    }

    /**
     * Test if captured data is returned for the transaction id
     *
     * @magentoDataFixture   Magento/Sales/_files/order.php
     *
     * @return void
     * @throws LocalizedException
     */
    public function testCaptureDataByTransactionIdReturnsExpectedData(): void
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $payment = $order->getPayment();

        $transactionId = '12345';
        $expectedData = ['some' => 'data'];

        $captureUtilMock = $this->getMockBuilder(CaptureUtil::class)->setConstructorArgs(
            [
                $this->getObjectManager()->get(DateTime::class)
            ]
        )->getMock();

        $captureUtilMock->expects(self::once())
            ->method('getCaptureDataByTransactionId')->with($transactionId)->willReturn($expectedData);

        $actualData = $captureUtilMock->getCaptureDataByTransactionId($transactionId, $payment);

        self::assertSame($expectedData, $actualData);
    }

    /**
     * Test if captured data is null for non-existing transaction id
     *
     * @magentoDataFixture   Magento/Sales/_files/order.php
     *
     * @return void
     * @throws LocalizedException
     */
    public function testCaptureDataByTransactionIdReturnsNullForNonExistingId(): void
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $payment = $order->getPayment();

        $nonExistingTransactionId = 'non-existing-id';

        $actualData = $this->captureUtil->getCaptureDataByTransactionId($nonExistingTransactionId, $payment);

        self::assertNull($actualData);
    }
}
