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

namespace MultiSafepay\ConnectCore\Test\Integration\Service\Process;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\Process\ChangePaymentMethod;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\GiftcardUtil;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;

class ChangePaymentMethodTest extends AbstractTestCase
{
    /**
     * @var ChangePaymentMethod
     */
    private $changePaymentMethod;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var GiftcardUtil
     */
    private $giftcardUtil;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $objectManager = $this->getObjectManager();

        $this->logger = $this->createMock(Logger::class);
        $this->config = $this->createMock(Config::class);
        $this->giftcardUtil = $this->createMock(GiftcardUtil::class);
        $this->paymentMethodUtil = $this->createMock(PaymentMethodUtil::class);

        $this->changePaymentMethod = $objectManager->create(
            ChangePaymentMethod::class,
            [
                'logger' => $this->logger,
                'config' => $this->config,
                'giftcardUtil' => $this->giftcardUtil,
                'paymentMethodUtil' => $this->paymentMethodUtil
            ]
        );
    }

    /**
     * Test changing the payment method with a valid transaction.
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws LocalizedException
     * @throws Exception
     */
    public function testExecuteWithValidPaymentMethodChange(): void
    {
        /** @var Order $order */
        $order = $this->getOrder();

        $transaction = [
            'payment_details' => [
                'type' => 'IDEAL'
            ]
        ];

        $this->paymentMethodUtil->expects($this->once())
            ->method('isMultisafepayOrder')
            ->with($order)
            ->willReturn(true);

        $this->giftcardUtil->expects($this->once())
            ->method('isFullGiftcardTransaction')
            ->with($transaction)
            ->willReturn(false);

        $this->config->expects($this->once())
            ->method('getValueByPath')
            ->with('payment')
            ->willReturn([
                'multisafepay_ideal' => [
                    'gateway_code' => 'IDEAL'
                ]
            ]);

        $this->logger->expects($this->exactly(3))
            ->method('logInfoForNotification');

        $result = $this->changePaymentMethod->execute($order, $transaction);

        $this->assertTrue($result[StatusOperationInterface::SUCCESS_PARAMETER]);
        $this->assertEquals('multisafepay_ideal', $order->getPayment()->getMethod());
    }

    /**
     * Test changing the payment method when the payment is null.
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws Exception
     */
    public function testExecuteWithNullPayment(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getIncrementId')->willReturn('100000001');
        $order->method('getPayment')->willReturn(null);

        $transaction = [
            'payment_details' => [
                'type' => 'IDEAL'
            ]
        ];

        $this->logger->expects($this->once())
            ->method('logInfoForNotification')
            ->with(
                '100000001',
                'Payment method could not be changed, because the payment was not found',
                $transaction
            );

        $result = $this->changePaymentMethod->execute($order, $transaction);

        $this->assertFalse($result[StatusOperationInterface::SUCCESS_PARAMETER]);
        $this->assertEquals(
            'Payment method could not be changed, because the payment was not found',
            $result[StatusOperationInterface::MESSAGE_PARAMETER]
        );
    }

    /**
     * Test changing the payment method with a credit card vault transaction.
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws LocalizedException
     * @throws Exception
     */
    public function testExecuteWithCreditCardVault(): void
    {
        /** @var Order $order */
        $order = $this->getOrder();

        $payment = $order->getPayment();
        $paymentMethod = $this->createMock(MethodInterface::class);
        $paymentMethod->method('getConfigData')
            ->with('gateway_code')
            ->willReturn('CREDITCARD');
        $payment->setMethodInstance($paymentMethod);

        $transaction = [
            'payment_details' => [
                'type' => 'VISA'
            ]
        ];

        $this->paymentMethodUtil->expects($this->never())
            ->method('isMultisafepayOrder')
            ->with($order)
            ->willReturn(true);

        $this->logger->expects($this->exactly(2))
            ->method('logInfoForNotification');

        $result = $this->changePaymentMethod->execute($order, $transaction);

        $this->assertTrue($result[StatusOperationInterface::SUCCESS_PARAMETER]);
        $this->assertNotEquals('multisafepay_visa', $order->getPayment()->getMethod());
    }

    /**
     * Test changing the payment method with a gift card transaction.
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws LocalizedException
     * @throws Exception
     */
    public function testExecuteWithGiftcardTransaction(): void
    {
        /** @var Order $order */
        $order = $this->getOrder();

        $transaction = [
            'payment_details' => [
                'type' => 'GIFTCARD'
            ]
        ];

        $this->paymentMethodUtil->expects($this->once())
            ->method('isMultisafepayOrder')
            ->with($order)
            ->willReturn(true);

        $this->giftcardUtil->expects($this->once())
            ->method('isFullGiftcardTransaction')
            ->with($transaction)
            ->willReturn(true);

        $this->giftcardUtil->expects($this->once())
            ->method('getGiftcardGatewayCodeFromTransaction')
            ->with($transaction)
            ->willReturn('WEBSHOPGIFTCARD');

        $this->config->expects($this->once())
            ->method('getValueByPath')
            ->with('payment')
            ->willReturn([
                'multisafepay_webshopgiftcard' => [
                    'gateway_code' => 'WEBSHOPGIFTCARD'
                ]
            ]);

        $this->logger->expects($this->exactly(3))
            ->method('logInfoForNotification');

        $result = $this->changePaymentMethod->execute($order, $transaction);

        $this->assertTrue($result[StatusOperationInterface::SUCCESS_PARAMETER]);
    }

    /**
     * Test changing the payment method with a coupon transaction for Intersolve.
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws LocalizedException
     * @throws Exception
     */
    public function testExecuteWithCouponIntersolve(): void
    {
        /** @var Order $order */
        $order = $this->getOrder();

        $transaction = [
            'payment_details' => [
                'type' => 'Coupon::Intersolve'
            ]
        ];

        $this->paymentMethodUtil->expects($this->never())->method('isMultisafepayOrder');
        $this->giftcardUtil->expects($this->never())->method('isFullGiftcardTransaction');
        $this->logger->expects($this->exactly(2))->method('logInfoForNotification');

        $result = $this->changePaymentMethod->execute($order, $transaction);

        $this->assertTrue($result[StatusOperationInterface::SUCCESS_PARAMETER]);
    }

    /**
     * Test executing the change payment method process with a non-MultiSafepay order.
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws LocalizedException
     * @throws Exception
     */
    public function testExecuteWithNonMultisafepayOrder(): void
    {
        /** @var Order $order */
        $order = $this->getOrder();

        $transaction = [
            'payment_details' => [
                'type' => 'IDEAL'
            ]
        ];

        $this->paymentMethodUtil->expects($this->once())
            ->method('isMultisafepayOrder')
            ->with($order)
            ->willReturn(false);

        $this->giftcardUtil->expects($this->never())->method('isFullGiftcardTransaction');
        $this->logger->expects($this->exactly(2))->method('logInfoForNotification');

        $result = $this->changePaymentMethod->execute($order, $transaction);

        $this->assertTrue($result[StatusOperationInterface::SUCCESS_PARAMETER]);
    }

    /**
     * Test executing the change payment method process with the same payment method.
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws LocalizedException
     * @throws Exception
     */
    public function testExecuteWithSamePaymentMethod(): void
    {
        /** @var Order $order */
        $order = $this->getOrder();

        $payment = $order->getPayment();
        $paymentMethod = $this->createMock(MethodInterface::class);
        $paymentMethod->method('getConfigData')->with('gateway_code')->willReturn('IDEAL');
        $payment->setMethodInstance($paymentMethod);

        $transaction = [
            'payment_details' => [
                'type' => 'IDEAL'
            ]
        ];

        $this->paymentMethodUtil->expects($this->never())->method('isMultisafepayOrder');
        $this->giftcardUtil->expects($this->never())->method('isFullGiftcardTransaction');
        $this->logger->expects($this->exactly(2))->method('logInfoForNotification');

        $result = $this->changePaymentMethod->execute($order, $transaction);

        $this->assertTrue($result[StatusOperationInterface::SUCCESS_PARAMETER]);
    }

    /**
     * Test executing the change payment method process with a recurring payment method.
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws LocalizedException
     * @throws Exception
     */
    public function testExecuteWithRecurringPaymentMethod(): void
    {
        /** @var Order $order */
        $order = $this->getOrder();

        $transaction = [
            'payment_details' => [
                'type' => 'IDEAL'
            ]
        ];

        $this->paymentMethodUtil->expects($this->once())
            ->method('isMultisafepayOrder')
            ->with($order)
            ->willReturn(true);

        $this->giftcardUtil->expects($this->once())
            ->method('isFullGiftcardTransaction')
            ->with($transaction)
            ->willReturn(false);

        $this->config->expects($this->once())
            ->method('getValueByPath')
            ->with('payment')
            ->willReturn([
                'multisafepay_ideal_recurring' => [
                    'gateway_code' => 'IDEAL'
                ]
            ]);

        $this->logger->expects($this->exactly(2))
            ->method('logInfoForNotification');

        $result = $this->changePaymentMethod->execute($order, $transaction);

        $this->assertTrue($result[StatusOperationInterface::SUCCESS_PARAMETER]);
        // Should not change to recurring payment method
        $this->assertNotEquals('multisafepay_ideal_recurring', $order->getPayment()->getMethod());
    }
}
