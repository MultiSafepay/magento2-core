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
use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\Data\OrderInterface;
use MultiSafepay\Api\Transactions\RefundRequest;
use MultiSafepay\ConnectCore\Gateway\Request\Builder\GenericGatewayRefundTransactionBuilder;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class GenericGatewayRefundTransactionBuilderTest extends AbstractTestCase
{
    /**
     * Test to see if this could be build
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws LocalizedException
     */
    public function testBuild(): void
    {
        $genericGatewayRefundTransactionBuilder = $this->getGenericGatewayRefundTransactionBuilder();
        $stateObject = new DataObject();
        $order = $this->getOrder();
        $paymentDataObject = $this->getNewPaymentDataObject($order);

        $buildSubject = [
            'stateObject' => $stateObject,
            'payment' => $paymentDataObject,
            'amount' => $order->getGrandTotal(),
            'currency' => $order->getOrderCurrencyCode()
        ];

        $return = $genericGatewayRefundTransactionBuilder->build($buildSubject);

        self::assertArrayHasKey('payload', $return);
        self::assertArrayHasKey('order_id', $return);
        self::assertInstanceOf(RefundRequest::class, $return['payload']);
    }

    /**
     * @return GenericGatewayRefundTransactionBuilder
     */
    private function getGenericGatewayRefundTransactionBuilder(): GenericGatewayRefundTransactionBuilder
    {
        return $this->getObjectManager()->get(GenericGatewayRefundTransactionBuilder::class);
    }

    /**
     * @param OrderInterface $order
     * @return PaymentDataObjectInterface
     */
    private function getNewPaymentDataObject(OrderInterface $order): PaymentDataObjectInterface
    {
        /** @var PaymentDataObjectFactoryInterface $paymentDataObjectFactory */
        $paymentDataObjectFactory = $this->getObjectManager()->get(PaymentDataObjectFactoryInterface::class);
        return $paymentDataObjectFactory->create($order->getPayment());
    }
}
