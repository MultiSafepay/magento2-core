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
 * Copyright © 2022 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Test\Integration\Gateway\Response;

use Exception;
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
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @throws Exception
     */
    public function testHandleForPartialCaptureResponse()
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $orderIncrementId = $order->getIncrementId();
        $transactionId = '111112222';
        $amount = 100;
        $response = [];
        $handlingSubject = [
            'payment' => $this->getNewPaymentDataObjectFromOrder($order),
            'amount' => $amount,
        ];

        $this->expectExceptionMessage('Capture response API data is not valid.');
        $this->expectException(LocalizedException::class);

        $this->captureResponseHandler->handle($handlingSubject, $response);

        $response = [
            'transaction_id' => $transactionId,
            'order_id' => $orderIncrementId,
        ];
        $this->captureResponseHandler->handle($handlingSubject, $response);
        $captureData = $this->captureUtil->getCaptureDataByTransactionId($transactionId, $order->getPayment());

        self::assertEquals($transactionId, $captureData['transaction_id']);
        self::assertEquals($orderIncrementId, $captureData['order_id']);
        self::assertEquals((float)$amount, $captureData['amount']);
    }
}
