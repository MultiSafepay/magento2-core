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

use Exception;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Exception\CouldNotRefundException;
use Magento\Store\Model\Store;
use MultiSafepay\ConnectCore\Gateway\Request\Builder\ShoppingCartRefundRequestBuilder;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class ShoppingCartRefundRequestBuilderTest extends AbstractTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws LocalizedException
     * @throws Exception
     */
    public function testBuildShoppingCartRefundWithoutCreditMemo(): void
    {
        $refundRequest = $this->getShoppingcartRefundRequestBuilder();
        $stateObject = new DataObject();
        $order = $this->getOrder();

        $paymentDataObject = $this->getNewPaymentDataObject($order);

        $buildSubject = [
            'stateObject' => $stateObject,
            'payment' => $paymentDataObject,
            'amount' => $order->getGrandTotal(),
            'currency' => $order->getOrderCurrencyCode()
        ];

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('The refund could not be created because the credit memo is missing');

        $refundRequest->build($buildSubject);
    }

    /**
     * Test to see if this could be build
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws LocalizedException
     * @throws Exception
     */
    public function testBuildShoppingCartRefundWithEmptyRefundAmount(): void
    {
        $refundTransactionBuilder = $this->getShoppingcartRefundRequestBuilder();
        $stateObject = new DataObject();
        $order = $this->getOrder();
        $order->getPayment()->setCreditMemo($this->getCreditMemo());
        $paymentDataObject = $this->getNewPaymentDataObject($order);

        $buildSubject = [
            'stateObject' => $stateObject,
            'payment' => $paymentDataObject,
            'amount' => 0,
            'currency' => $order->getOrderCurrencyCode()
        ];

        $this->expectException(CouldNotRefundException::class);
        $this->expectExceptionMessage('Refunds with 0 amount can not be processed. Please set a different amount');

        $refundTransactionBuilder->build($buildSubject);
    }

    /**
     * Test to see if a shopping cart refund could be build
     *
     * @magentoDataFixture Magento/Sales/_files/creditmemo_for_get.php
     * @throws LocalizedException
     * @throws Exception
     */
    public function testBuildShoppingCartRefund(): void
    {
        $refundRequest = $this->getShoppingcartRefundRequestBuilder();
        $stateObject = new DataObject();
        $order = $this->getOrder();
        $order->getPayment()->setCreditMemo($this->getCreditMemo());

        $paymentDataObject = $this->getNewPaymentDataObject($order);

        $buildSubject = [
            'stateObject' => $stateObject,
            'payment' => $paymentDataObject,
            'amount' => $order->getGrandTotal(),
            'currency' => $order->getOrderCurrencyCode()
        ];

        $result = $refundRequest->build($buildSubject);

        self::assertArrayHasKey('money', $result);
        self::assertArrayHasKey('payload', $result);
        self::assertArrayHasKey('order_id', $result);
        self::assertArrayHasKey(Store::STORE_ID, $result);
    }

    /**
     * @return ShoppingCartRefundRequestBuilder
     */
    private function getShoppingcartRefundRequestBuilder(): ShoppingCartRefundRequestBuilder
    {
        return $this->getObjectManager()->get(ShoppingCartRefundRequestBuilder::class);
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

    /**
     * @return CreditmemoInterface
     * @throws Exception
     */
    protected function getCreditMemo(): CreditmemoInterface
    {
        return $this->getObjectManager()->create(CreditmemoInterface::class);
    }
}
