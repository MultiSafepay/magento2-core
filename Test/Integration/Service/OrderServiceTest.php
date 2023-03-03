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

namespace MultiSafepay\ConnectCore\Test\Integration\Service;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\StatusResolver;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\ConnectCore\Model\SecondChance;
use MultiSafepay\ConnectCore\Model\Vault;
use MultiSafepay\ConnectCore\Service\Order\PayMultisafepayOrder;
use MultiSafepay\ConnectCore\Service\Order\ProcessChangePaymentMethod;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\InvoiceUtil;
use ReflectionException;
use ReflectionObject;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderServiceTest extends AbstractTestCase
{
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
     * @var PayMultisafepayOrder
     */
    private $payMultisafepayOrder;

    /**
     * @var ProcessChangePaymentMethod
     */
    private $processChangePaymentMethod;

    /**
     * @var ReflectionObject
     */
    private $processChangePaymentMethodReflector;

    /**
     * @var InvoiceUtil
     */
    private $invoiceUtil;

    /**
     * @throws LocalizedException
     */
    protected function setUp(): void
    {
        $this->getObjectManager()->get(State::class)->setAreaCode(Area::AREA_FRONTEND);
        $this->payMultisafepayOrder = $this->getObjectManager()->create(PayMultisafepayOrder::class);
        $this->processChangePaymentMethod = $this->getObjectManager()->create(ProcessChangePaymentMethod::class);
        $this->invoiceUtil = $this->getObjectManager()->create(InvoiceUtil::class);
        $this->vault = $this->getObjectManager()->create(Vault::class);
        $this->secondChance = $this->getObjectManager()->create(SecondChance::class);
        $this->statusResolver = $this->getObjectManager()->create(StatusResolver::class);
        $this->processChangePaymentMethodReflector
            = new ReflectionObject($this->processChangePaymentMethod);
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
        $this->payMultisafepayOrder->execute($order, $payment, $transaction->getData());

        self::assertEquals($fakeTransactionId, $payment->getLastTransId());
        self::assertTrue($payment->getIsTransactionApproved());

        $invoices = $this->invoiceUtil->getInvoicesByOrderId($order->getId());
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
