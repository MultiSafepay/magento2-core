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

namespace MultiSafepay\ConnectCore\Test\Integration\Gateway;

use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Http\Transfer;
use Magento\Sales\Api\Data\OrderInterface;
use MultiSafepay\Api\TransactionManager;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\Sdk;
use PHPUnit\Framework\MockObject\MockObject;

class AbstractGatewayTestCase extends AbstractTestCase
{
    /**
     * @param OrderInterface $order
     * @return PaymentDataObjectInterface
     */
    protected function getNewPaymentDataObjectFromOrder(OrderInterface $order): PaymentDataObjectInterface
    {
        /** @var PaymentDataObjectFactoryInterface $paymentDataObjectFactory */
        $paymentDataObjectFactory = $this->getObjectManager()->get(PaymentDataObjectFactoryInterface::class);

        return $paymentDataObjectFactory->create($order->getPayment());
    }

    /**
     * @param string $orderId
     * @param array $transactionData
     * @return MockObject
     */
    protected function getSdkMockWithPartialCapture(
        string $orderId,
        array $transactionData
    ): MockObject {
        $sdk = $this->getMockBuilder(Sdk::class)
            ->disableOriginalConstructor()
            ->getMock();

        $transactionManagerMock = $this->getMockBuilder(TransactionManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockResponse = $this->getMockBuilder(TransactionResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockResponse->expects($this->any())
            ->method('getData')
            ->willReturn($transactionData);

        $transactionManagerMock->expects($this->any())
            ->method('get')
            ->with($orderId)
            ->willReturn($mockResponse);

        $sdk->expects($this->any())
            ->method('getTransactionManager')
            ->willReturn($transactionManagerMock);

        return $sdk;
    }

    /**
     * @param $body
     * @return MockObject
     */
    protected function prepareTransferObjectMock($body): MockObject
    {
        $transferObject = $this->getMockBuilder(Transfer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $transferObject->expects($this->any())
            ->method('getBody')
            ->willReturn($body);

        return $transferObject;
    }
}
