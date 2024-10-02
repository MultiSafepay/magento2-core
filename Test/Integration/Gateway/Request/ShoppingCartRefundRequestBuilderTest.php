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
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Exception\CouldNotRefundException;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Gateway\Request\Builder\ShoppingCartRefundRequestBuilder;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\CurrencyUtil;
use MultiSafepay\ConnectCore\Util\ShoppingCartRefundUtil;
use MultiSafepay\ConnectCore\Util\TransactionUtil;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
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
        $transactionUtilMock = $this->getMockBuilder(TransactionUtil::class)
            ->disableOriginalConstructor()
            ->getMock();

        $transactionUtilMock->method('getTransaction')->willReturn($this->getTransactionResponse());

        $refundRequest = $this->getMockBuilder(ShoppingCartRefundRequestBuilder::class)->setConstructorArgs([
            $this->getObjectManager()->get(CurrencyUtil::class),
            $this->getObjectManager()->get(Logger::class),
            $transactionUtilMock,
            $this->getObjectManager()->get(ShoppingCartRefundUtil::class),
        ])->setMethodsExcept(['build'])->getMock();

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

        self::assertArrayHasKey('order_id', $result);
        self::assertEquals('100000001', $result['order_id']);

        self::assertArrayHasKey('store_id', $result);
        self::assertEquals(1, $result['store_id']);

        self::assertArrayHasKey('currency', $result);
        self::assertEquals('USD', $result['currency']);

        self::assertArrayHasKey('items', $result);
        self::assertArrayHasKey('shipping', $result);
        self::assertArrayHasKey('adjustment', $result);

        self::assertArrayHasKey('transaction', $result);
        self::assertInstanceOf(TransactionResponse::class, $result['transaction']);
    }

    /**
     * @return ShoppingCartRefundRequestBuilder
     */
    private function getShoppingCartRefundRequestBuilder(): ShoppingCartRefundRequestBuilder
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
     */
    protected function getCreditMemo(): CreditmemoInterface
    {
        return $this->getObjectManager()->create(CreditmemoInterface::class);
    }

    /**
     * @return TransactionResponse
     */
    protected function getTransactionResponse(): TransactionResponse
    {
        return $this->getObjectManager()->create(TransactionResponse::class);
    }
}
