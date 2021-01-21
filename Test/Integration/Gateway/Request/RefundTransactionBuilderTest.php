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

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Exception\CouldNotRefundException;
use MultiSafepay\Api\Transactions\RefundRequest;
use MultiSafepay\ConnectCore\Gateway\Request\Builder\RefundTransactionBuilder;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class RefundTransactionBuilderTest extends AbstractTestCase
{
    /**
     * Test to see if this could be build
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws LocalizedException
     */
    public function testBuild()
    {
        $refundTransactionBuilder = $this->getRefundTransactionBuilder();
        $stateObject = new DataObject();
        $order = $this->getOrder();
        $paymentDataObject = $this->getNewPaymentDataObject($order);

        $buildSubject = [
            'stateObject' => $stateObject,
            'payment' => $paymentDataObject,
            'amount' => $order->getGrandTotal(),
            'currency' => $order->getOrderCurrencyCode()
        ];

        $return = $refundTransactionBuilder->build($buildSubject);

        $this->assertArrayHasKey('payload', $return);
        $this->assertArrayHasKey('order_id', $return);
        $this->assertInstanceOf(RefundRequest::class, $return['payload']);
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
        $paymentDataObject = $this->getNewPaymentDataObject($order);

        $buildSubject = [
            'stateObject' => $stateObject,
            'payment' => $paymentDataObject,
            'amount' => 0,
            'currency' => $order->getOrderCurrencyCode()
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
        $paymentDataObject = $this->getNewPaymentDataObject($order);

        $buildSubject = [
            'stateObject' => $stateObject,
            'payment' => $paymentDataObject,
            'amount' => -1,
            'currency' => $order->getOrderCurrencyCode()
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
