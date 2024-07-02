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
use MultiSafepay\Api\Transactions\CaptureRequest;
use MultiSafepay\Api\Transactions\Transaction;
use MultiSafepay\ConnectCore\Gateway\Request\Builder\CancelTransactionBuilder;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Test\Integration\Gateway\AbstractGatewayTestCase;
use MultiSafepay\ConnectCore\Util\CaptureUtil;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CancelTransactionBuilderTest extends AbstractGatewayTestCase
{
    /**
     * @var array
     */
    private $transactionData;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->transactionData = $this->getManualCaptureTransactionData() ?? [];
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/multisafepay_visa/payment_action authorize
     * @magentoConfigFixture default_store payment/multisafepay_visa/manual_capture 1
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @throws Exception
     */
    public function testSuccessCancelBuildForPartialCaptureTransaction(): void
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $orderIncrementId = $order->getIncrementId();
        $cancelTransactionBuilder = $this->getCancelTransactionBuilderMock($orderIncrementId, $this->transactionData);
        $cancelTransactionData = $cancelTransactionBuilder->build(
            [
                'payment' => $this->getNewPaymentDataObjectFromOrder($order),
            ]
        );
        $cancelPayload = $cancelTransactionData['payload'];

        self::assertEquals($orderIncrementId, $cancelTransactionData['order_id']);
        self::assertEquals(Transaction::CANCELLED, $cancelPayload->get('status'));
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/multisafepay_visa/payment_action authorize
     * @magentoConfigFixture default_store payment/multisafepay_visa/manual_capture 1
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @throws Exception
     */
    public function testCancelBuildForNonPartialCaptureTransactions(): void
    {
        $order = $this->getOrder();
        $orderIncrementId = $order->getIncrementId();
        $transactionData = $this->transactionData;
        $cancelTransactionData = $this->getCancelTransactionBuilderMock($orderIncrementId, $transactionData)
            ->build(
                [
                    'payment' => $this->getNewPaymentDataObjectFromOrder($order),
                ]
            );

        self::assertEquals($orderIncrementId, $cancelTransactionData['order_id']);
        self::assertArrayNotHasKey('payload', $cancelTransactionData);

        unset($transactionData['payment_details']['capture']);
        $order = $this->getOrderWithVisaPaymentMethod();
        $buildSubject = [
            'payment' => $this->getNewPaymentDataObjectFromOrder($order),
        ];

        self::assertArrayNotHasKey(
            'payload',
            $this->getCancelTransactionBuilderMock($orderIncrementId, $transactionData)->build($buildSubject)
        );

        $transactionData = $this->transactionData;
        $transactionData['payment_details']['capture_expiry'] = '2000-01-01T11:42:00';

        self::assertArrayNotHasKey(
            'payload',
            $this->getCancelTransactionBuilderMock($orderIncrementId, $transactionData)->build($buildSubject)
        );
    }

    /**
     * @param string $orderId
     * @param array $transactionData
     * @return MockObject
     */
    private function getCancelTransactionBuilderMock(
        string $orderId,
        array $transactionData
    ): MockObject {
        return $this->getMockBuilder(CancelTransactionBuilder::class)
            ->setConstructorArgs([
                $this->getObjectManager()->get(CaptureUtil::class),
                $this->setupSdkFactory($this->getSdkMockWithPartialCapture($orderId, $transactionData)),
                $this->getObjectManager()->get(CaptureRequest::class),
                $this->getObjectManager()->get(Logger::class),
                $this->getObjectManager()->get(PaymentMethodUtil::class),
            ])
            ->setMethodsExcept(['build'])
            ->getMock();
    }
}
