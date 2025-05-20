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

namespace MultiSafepay\ConnectCore\Test\Integration\Plugin\Sales\Model\Order;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\CustomerManagement;
use MultiSafepay\Api\Transactions\Transaction;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\CreditCardConfigProvider;
use MultiSafepay\ConnectCore\Plugin\Sales\Model\Order\CustomerManagementPlugin;
use MultiSafepay\ConnectCore\Service\Process\SetOrderProcessingStatus;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;
use MultiSafepay\ConnectCore\Util\TransactionUtil;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class CustomerManagementPluginTest extends AbstractTestCase
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var TransactionUtil
     */
    private $transactionUtil;

    /**
     * @var SetOrderProcessingStatus
     */
    private $setOrderProcessingStatus;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->orderRepository = $this->getObjectManager()->create(OrderRepositoryInterface::class);
        $this->paymentMethodUtil = $this->getObjectManager()->create(PaymentMethodUtil::class);
        $this->transactionUtil = $this->getObjectManager()->create(TransactionUtil::class);
        $this->setOrderProcessingStatus = $this->getObjectManager()->create(SetOrderProcessingStatus::class);
    }

    /**
     * Tests that the afterCreate method of the CustomerManagementPlugin class skips action when order is not found
     *
     * @return void
     * @throws LocalizedException
     */
    public function testAfterCreateSkipsActionWhenOrderNotFound()
    {
        $loggerMock = $this->createMock(Logger::class);
        $loggerMock->expects($this->once())->method('logInfoForOrder')
            ->with('unknown', $this->stringContains('could not be found, skipping action'));

        $plugin = new CustomerManagementPlugin(
            $this->orderRepository,
            $this->paymentMethodUtil,
            $this->transactionUtil,
            $this->setOrderProcessingStatus,
            $loggerMock
        );

        $plugin->afterCreate($this->getObjectManager()->create(CustomerManagement::class), null, 123);
    }

    /**
     * Tests that the afterCreate method skips action when order is not a MultiSafepay order
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     *
     * @return void
     * @throws LocalizedException
     */
    public function testAfterCreateSkipsActionWhenNotMultisafepayOrder()
    {
        $order = $this->getOrder();

        $loggerMock = $this->createMock(Logger::class);
        $loggerMock->expects($this->never())->method('logInfoForOrder');

        $plugin = new CustomerManagementPlugin(
            $this->orderRepository,
            $this->paymentMethodUtil,
            $this->transactionUtil,
            $this->setOrderProcessingStatus,
            $loggerMock
        );

        $plugin->afterCreate($this->createMock(CustomerManagement::class), null, $order->getId());
    }

    /**
     * Tests that the afterCreate method sets the order to the processing state when the transaction is completed and
     * the order is in pending payment state
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     *
     * @return void
     * @throws LocalizedException
     */
    public function testAfterCreateSetsOrderToProcessingWhenTransactionCompletedAndOrderIsPendingPayment()
    {
        $order = $this->getOrderWithTestCriteria();

        $transactionMock = $this->createMock(TransactionResponse::class);
        $transactionMock->method('getStatus')->willReturn(Transaction::COMPLETED);
        $transactionMock->method('getData')->willReturn(['status' => Transaction::COMPLETED]);

        $transactionUtilMock = $this->createMock(TransactionUtil::class);
        $transactionUtilMock->method('getTransaction')->willReturn($transactionMock);

        $loggerMock = $this->createMock(Logger::class);
        $loggerMock->expects($this->exactly(2))->method('logInfoForOrder');
        $loggerMock->expects($this->once())
            ->method('logInfoForNotification')
            ->with(
                $order->getIncrementId(),
                $this->stringContains('Order state has been changed to'),
                ['status' => Transaction::COMPLETED]
            );

        $plugin = new CustomerManagementPlugin(
            $this->orderRepository,
            $this->paymentMethodUtil,
            $transactionUtilMock,
            $this->setOrderProcessingStatus,
            $loggerMock
        );

        $plugin->afterCreate($this->createMock(CustomerManagement::class), null, $order->getId());

        $order = $this->getOrder();

        $this->assertEquals(Order::STATE_PROCESSING, $order->getState());
        $this->assertEquals(Order::STATE_PROCESSING, $order->getStatus());
    }

    /**
     * Tests that the afterCreate method skips action when the transaction is not completed
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     *
     * @return void
     * @throws LocalizedException
     */
    public function testAfterCreateSkipsActionWhenTransactionNotCompleted()
    {
        $order = $this->getOrderWithTestCriteria();

        $transactionMock = $this->createMock(TransactionResponse::class);
        $transactionMock->method('getStatus')->willReturn(Transaction::INITIALIZED);

        $transactionUtilMock = $this->createMock(TransactionUtil::class);
        $transactionUtilMock->method('getTransaction')->willReturn($transactionMock);

        $loggerMock = $this->createMock(Logger::class);
        $loggerMock->expects($this->once())->method('logInfoForOrder')
            ->with(
                $order->getIncrementId(),
                $this->stringContains('checking if order needs to be set to the processing state.')
            );
        $loggerMock->expects($this->never())->method('logInfoForNotification');

        $plugin = new CustomerManagementPlugin(
            $this->orderRepository,
            $this->paymentMethodUtil,
            $transactionUtilMock,
            $this->setOrderProcessingStatus,
            $loggerMock
        );

        $plugin->afterCreate($this->createMock(CustomerManagement::class), null, $order->getId());
    }

    /**
     * Change the order state to pending_payment, set the payment method to MultiSafepay and return it
     *
     * @return void
     * @throws LocalizedException
     */
    private function getOrderWithTestCriteria(): OrderInterface
    {
        $order = $this->getOrder();

        $payment = $this->getObjectManager()->create(Payment::class);
        $payment->setMethod(CreditCardConfigProvider::CODE);

        $order->setState(Order::STATE_PENDING_PAYMENT);
        $order->setStatus(Order::STATE_PENDING_PAYMENT);
        $order->setPayment($payment);

        return $this->orderRepository->save($order);
    }
}
