<?php declare(strict_types=1);

namespace MultiSafepay\Test\Integration\Util;

use Magento\Framework\Exception\LocalizedException;
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

    public function testIsCaptureManualTransaction(): void
    {
        self::assertTrue($this->captureUtil->isCaptureManualTransaction(
            $this->transactionData
        ));
    }

    public function testIsCaptureManualReservationExpired(): void
    {
        $transactionData = $this->transactionData;

        self::assertFalse($this->captureUtil->isCaptureManualReservationExpired($transactionData));

        $transactionData['payment_details']['capture_expiry'] = '2000-01-01T11:42:00';

        self::assertTrue($this->captureUtil->isCaptureManualReservationExpired($transactionData));
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/multisafepay_visa/payment_action authorize
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @magentoConfigFixture default_store multisafepay/general/use_manual_capture 1
     *
     * @throws LocalizedException
     */
    public function testIsCaptureManualPayment(): void
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $payment = $order->getPayment();

        self::assertTrue($this->captureUtil->isCaptureManualPayment($payment));

        $payment->setMethod(CreditCardConfigProvider::CODE);
        $payment->setAdditionalInformation(
            CreditCardDataAssignObserver::CREDIT_CARD_BRAND_PARAM_NAME,
            'VISA'
        );

        self::assertTrue($this->captureUtil->isCaptureManualPayment($payment));

        $payment->setAdditionalInformation(
            CreditCardDataAssignObserver::CREDIT_CARD_BRAND_PARAM_NAME,
            'AMEX'
        );

        //self::assertFalse($this->captureUtil->isCaptureManualPayment($payment));

        //$payment->setMethod(CreditCardConfigProvider::VAULT_CODE);
        //
        //self::assertFalse($this->captureUtil->isCaptureManualPayment($payment));
    }
}
