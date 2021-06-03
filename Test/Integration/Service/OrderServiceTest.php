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

namespace MultiSafepay\ConnectCore\Test\Integration\Service;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\StatusResolver;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use MultiSafepay\Api\Transactions\Transaction as TransactionStatus;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\ConnectCore\Api\RecurringDetailsInterface;
use MultiSafepay\ConnectCore\Model\SecondChance;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\AfterpayConfigProvider;
use MultiSafepay\ConnectCore\Model\Vault;
use MultiSafepay\ConnectCore\Service\OrderService;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use ReflectionException;
use ReflectionObject;

class OrderServiceTest extends AbstractTestCase
{
    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var Vault
     */
    private $vault;

    /**
     * @var SecondChance
     */
    private $secondChance;

    /**
     * @var StatusResolver
     */
    private $statusResolver;

    /**
     * @var ReflectionObject
     */
    private $reflector;

    /**
     * @throws LocalizedException
     */
    protected function setUp(): void
    {
        $this->getObjectManager()->get(State::class)->setAreaCode(Area::AREA_FRONTEND);
        $this->orderService = $this->getObjectManager()->create(OrderService::class);
        $this->vault = $this->getObjectManager()->create(Vault::class);
        $this->secondChance = $this->getObjectManager()->create(SecondChance::class);
        $this->statusResolver = $this->getObjectManager()->create(StatusResolver::class);
        $this->reflector = new ReflectionObject($this->orderService);
    }

    /**
     * @magentoDataFixture     Magento/Sales/_files/order_with_customer.php
     * @magentoDbIsolation     enabled
     * @magentoAppIsolation    enabled
     * @throws LocalizedException
     * @throws Exception
     */
    public function testVaultInitialization(): void
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $payment = $order->getPayment();
        $payment->setAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE, true);
        $gatewayToken = '12312312312';

        $isVaultInitialized = $this->vault->initialize($order->getPayment(), [
            RecurringDetailsInterface::RECURRING_ID => $gatewayToken,
            RecurringDetailsInterface::TYPE => TransactionStatus::COMPLETED,
            RecurringDetailsInterface::EXPIRATION_DATE => '2512',
            RecurringDetailsInterface::CARD_LAST4 => '1111',
        ]);

        self::assertTrue($isVaultInitialized);

        if ($isVaultInitialized) {
            $vaultData = $payment->getExtensionAttributes()->getVaultPaymentToken()->getData();

            self::assertEquals($gatewayToken, $vaultData[PaymentTokenInterface::GATEWAY_TOKEN]);
        }
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order_with_customer.php
     * @throws LocalizedException
     * @throws ReflectionException
     */
    public function testChangePaymentMethod(): void
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $payment = $order->getPayment();
        $gatewayCode = $payment->getMethodInstance()->getConfigData('gateway_code');
        $transactionType = 'AFTERPAY';
        $canChangePaymentMethod = $this->reflector->getMethod('canChangePaymentMethod');
        $canChangePaymentMethod->setAccessible(true);

        self::assertTrue($canChangePaymentMethod->invoke($this->orderService, $transactionType, $gatewayCode, $order));

        $changePaymentMethod = $this->reflector->getMethod('changePaymentMethod');
        $changePaymentMethod->setAccessible(true);
        $changePaymentMethod->invoke($this->orderService, $order, $payment, $transactionType);

        self::assertEquals(AfterpayConfigProvider::CODE, $payment->getMethod());
    }

    /**
     * @magentoDataFixture     Magento/Sales/_files/order_with_customer.php
     * @magentoDbIsolation     enabled
     * @magentoAppIsolation    enabled
     * @throws LocalizedException
     * @throws ReflectionException
     */
    public function testCompleteOrderTransaction(): void
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $payment = $order->getPayment();
        $fakeTransactionId = '12312312312';
        $transaction = new TransactionResponse();
        $transaction->addData(['transaction_id' => $fakeTransactionId]);

        $payOrderMethod = $this->reflector->getMethod('payOrder');
        $payOrderMethod->setAccessible(true);
        $payOrderMethod->invoke($this->orderService, $order, $payment, $transaction);

        self::assertEquals($fakeTransactionId, $payment->getLastTransId());
        self::assertTrue($payment->getIsTransactionApproved());

        $getInvoicesByOrderIdMethod = $this->reflector->getMethod('getInvoicesByOrderId');
        $getInvoicesByOrderIdMethod->setAccessible(true);
        $invoices = $getInvoicesByOrderIdMethod->invoke($this->orderService, $order->getId());
        $invoice = reset($invoices);

        self::assertEquals($fakeTransactionId, $invoice->getTransactionId());
        self::assertEquals($order->getGrandTotal(), $invoice->getGrandTotal());
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order_with_customer.php
     * @throws LocalizedException
     */
    public function testReopenOrderCancelledOrder(): void
    {
        $order = $this->getOrderWithVisaPaymentMethod()->cancel();

        self::assertEquals(Order::STATE_CANCELED, $order->getState());

        $this->secondChance->reopenOrder($order);
        $state = Order::STATE_NEW;
        $orderStatus = $this->statusResolver->getOrderStatusByState($order, $state);

        self::assertEquals($state, $order->getState());
        self::assertEquals($orderStatus, $order->getStatus());
    }
}
