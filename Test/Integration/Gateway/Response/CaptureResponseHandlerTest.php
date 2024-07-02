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

namespace MultiSafepay\ConnectCore\Test\Integration\Gateway\Response;

use Exception;
use InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use MultiSafepay\ConnectCore\Gateway\Response\CaptureResponseHandler;
use MultiSafepay\ConnectCore\Test\Integration\Gateway\AbstractGatewayTestCase;
use MultiSafepay\ConnectCore\Util\CaptureUtil;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CaptureResponseHandlerTest extends AbstractGatewayTestCase
{
    /**
     * @var CaptureResponseHandler
     */
    private $captureResponseHandler;

    /**
     * @var CaptureUtil
     */
    private $captureUtil;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->captureResponseHandler = $this->getObjectManager()->get(CaptureResponseHandler::class);
        $this->captureUtil = $this->getObjectManager()->get(CaptureUtil::class);
    }

    /**
     * Test handle method for successful partial capture response
     *
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @throws Exception
     */
    public function testHandleForPartialCaptureResponse()
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $orderIncrementId = $order->getIncrementId();
        $transactionId = '111112222';
        $amount = 100;

        $response = [
            'transaction_id' => $transactionId,
            'order_id' => $orderIncrementId,
        ];

        $handlingSubject = [
            'payment' => $this->getNewPaymentDataObjectFromOrder($order),
            'amount' => $amount,
        ];

        $this->captureResponseHandler->handle($handlingSubject, $response);
        $captureData = $this->captureUtil->getCaptureDataByTransactionId($transactionId, $order->getPayment());

        self::assertEquals($transactionId, $captureData['transaction_id']);
        self::assertEquals($orderIncrementId, $captureData['order_id']);
        self::assertEquals((float)$amount, $captureData['amount']);
    }

    /**
     * Test if the handle method throws an exception for invalid transaction ID
     *
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @throws Exception
     */
    public function testHandleThrowsExceptionForInvalidTransactionId()
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $orderIncrementId = $order->getIncrementId();
        $amount = 100;

        $response = [
            'transaction_id' => null,
            'order_id' => $orderIncrementId,
        ];

        $handlingSubject = [
            'payment' => $this->getNewPaymentDataObjectFromOrder($order),
            'amount' => $amount,
        ];

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Capture response API data is not valid.');
        $this->captureResponseHandler->handle($handlingSubject, $response);
    }

    /**
     * Test if the handle method throws an exception for an invalid order ID
     *
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @throws Exception
     */
    public function testHandleThrowsExceptionForInvalidOrderId()
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $transactionId = '111112222';
        $amount = 100;

        $response = [
            'transaction_id' => $transactionId,
            'order_id' => null,
        ];

        $handlingSubject = [
            'payment' => $this->getNewPaymentDataObjectFromOrder($order),
            'amount' => $amount,
        ];

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Capture response API data is not valid.');
        $this->captureResponseHandler->handle($handlingSubject, $response);
    }

    /**
     * Test if the handle method throws an exception for an invalid amount
     *
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @throws Exception
     */
    public function testHandleThrowsExceptionForInvalidAmount()
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $orderIncrementId = $order->getIncrementId();
        $transactionId = '111112222';

        $response = [
            'transaction_id' => $transactionId,
            'order_id' => $orderIncrementId,
        ];

        $handlingSubject = [
            'payment' => $this->getNewPaymentDataObjectFromOrder($order),
            'amount' => null,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->ExpectExceptionMessage('Amount should be provided');
        $this->captureResponseHandler->handle($handlingSubject, $response);
    }
}
