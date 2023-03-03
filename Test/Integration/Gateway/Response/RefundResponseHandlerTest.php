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

use Magento\Framework\Exception\LocalizedException;
use MultiSafepay\ConnectCore\Gateway\Response\RefundResponseHandler;
use MultiSafepay\ConnectCore\Test\Integration\Gateway\AbstractGatewayTestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RefundResponseHandlerTest extends AbstractGatewayTestCase
{
    /**
     * @var RefundResponseHandler
     */
    private $refundResponseHandler;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->refundResponseHandler = $this->getObjectManager()->get(RefundResponseHandler::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @throws LocalizedException
     */
    public function testRefundResponseHandle()
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $refundId = '111112222';
        $this->refundResponseHandler->handle(
            [
                'payment' => $this->getNewPaymentDataObjectFromOrder($order),
            ],
            [
                'refund_id' => $refundId,
            ]
        );

        $payment = $order->getPayment();

        self::assertEquals($refundId, $payment->getTransactionId());
        self::assertTrue($payment->getIsTransactionClosed());
    }
}
