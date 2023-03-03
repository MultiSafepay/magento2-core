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

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Exception\CouldNotRefundException;
use MultiSafepay\Api\Transactions\RefundRequest;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Gateway\Request\Builder\RefundTransactionBuilder;
use MultiSafepay\ConnectCore\Test\Integration\Gateway\AbstractGatewayTestCase;
use MultiSafepay\ConnectCore\Util\CaptureUtil;

class RefundTransactionBuilderTest extends AbstractGatewayTestCase
{
    /**
     * @var Config
     */
    private $config;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->config = $this->getObjectManager()->get(Config::class);
    }

    /**
     * Test to see if this could be build
     *
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @throws LocalizedException
     */
    public function testBuild(): void
    {
        $refundTransactionBuilder = $this->getRefundTransactionBuilder();
        $stateObject = new DataObject();
        $order = $this->getOrder();
        $paymentDataObject = $this->getNewPaymentDataObjectFromOrder($order);
        $currencyCode = $order->getOrderCurrencyCode() ?? 'USD';
        $amount = $order->getGrandTotal();

        $buildSubject = [
            'stateObject' => $stateObject,
            'payment' => $paymentDataObject,
            'amount' => $amount,
            'currency' => $currencyCode,
        ];

        $return = $refundTransactionBuilder->build($buildSubject);

        self::assertArrayHasKey('payload', $return);
        self::assertArrayHasKey('order_id', $return);
        self::assertInstanceOf(RefundRequest::class, $return['payload']);

        $payloadData = $return['payload']->getData();
        self::assertEquals($currencyCode, $payloadData['currency']);
        self::assertEquals(round($amount * 100, 10), $payloadData['amount']);
        self::assertEquals(
            $this->config->getRefundDescription($order->getIncrementId()),
            $payloadData['description']
        );
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function testRefundTransactionBuildForPartialCaptureTransaction()
    {
        $refundTransactionBuilder = $this->getRefundTransactionBuilder();
        $order = $this->getOrderWithVisaPaymentMethod();
        $payment = $order->getPayment();
        $transactionId = '1234567980';
        $newOrderId = $order->getIncrementId() . $transactionId;
        $buildSubject = [
            'stateObject' => new DataObject(),
            'payment' => $this->getNewPaymentDataObjectFromOrder($order),
            'amount' => 50,
            'currency' => $order->getOrderCurrencyCode(),
        ];
        $payment->setParentTransactionId($transactionId);
        $emptyCaptureDataExceptionMessage = 'Can\'t find manual capture data';

        $this->expectExceptionMessage($emptyCaptureDataExceptionMessage);
        $this->expectException(CouldNotRefundException::class);

        $refundTransactionBuilder->build($buildSubject);

        $this->expectExceptionMessage(
            'Refund amount for manual captured invoice is not valid. Please set a different amount'
        );
        $this->expectException(CouldNotRefundException::class);

        $payment->setAdditionalInformation(
            CaptureUtil::MULTISAFEPAY_CAPTURE_DATA_FIELD_NAME,
            [
                [
                    'transaction_id' => $transactionId,
                    'order_id' => $newOrderId,
                    'amount' => 20,
                ],
            ]
        );
        $refundTransactionBuilder->build($buildSubject);

        $this->expectExceptionMessage($emptyCaptureDataExceptionMessage);
        $this->expectException(CouldNotRefundException::class);

        $payment->setAdditionalInformation(
            CaptureUtil::MULTISAFEPAY_CAPTURE_DATA_FIELD_NAME,
            [
                [
                    'transaction_id' => $transactionId . '_123',
                    'order_id' => $newOrderId,
                    'amount' => 20,
                ],
            ]
        );
        $refundTransactionBuilder->build($buildSubject);

        $payment->setAdditionalInformation(
            CaptureUtil::MULTISAFEPAY_CAPTURE_DATA_FIELD_NAME,
            [
                [
                    'transaction_id' => $transactionId,
                    'order_id' => $newOrderId,
                    'amount' => 20,
                ],
            ]
        );
        $return = $refundTransactionBuilder->build($buildSubject);

        self::assertArrayHasKey('order_id', $return);
        self::assertEquals($newOrderId, $return['order_id']);
    }

    /**
     * Test to see if this could be build
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws LocalizedException
     */
    public function testBuildWithEmptyRefundAmount()
    {
        $refundTransactionBuilder = $this->getRefundTransactionBuilder();
        $stateObject = new DataObject();
        $order = $this->getOrder();
        $paymentDataObject = $this->getNewPaymentDataObjectFromOrder($order);

        $buildSubject = [
            'stateObject' => $stateObject,
            'payment' => $paymentDataObject,
            'amount' => 0,
            'currency' => $order->getOrderCurrencyCode(),
        ];

        $this->expectException(CouldNotRefundException::class);
        $refundTransactionBuilder->build($buildSubject);
    }

    /**
     * Test to see if this could be build
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws LocalizedException
     */
    public function testBuildWithWrongRefundAmount()
    {
        $refundTransactionBuilder = $this->getRefundTransactionBuilder();
        $stateObject = new DataObject();
        $order = $this->getOrder();
        $paymentDataObject = $this->getNewPaymentDataObjectFromOrder($order);

        $buildSubject = [
            'stateObject' => $stateObject,
            'payment' => $paymentDataObject,
            'amount' => -1,
            'currency' => $order->getOrderCurrencyCode(),
        ];

        $this->expectException(CouldNotRefundException::class);
        $refundTransactionBuilder->build($buildSubject);
    }

    /**
     * @return RefundTransactionBuilder
     */
    private function getRefundTransactionBuilder(): RefundTransactionBuilder
    {
        return $this->getObjectManager()->get(RefundTransactionBuilder::class);
    }
}
