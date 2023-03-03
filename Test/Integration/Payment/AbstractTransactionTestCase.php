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

namespace MultiSafepay\ConnectCore\Test\Integration\Payment;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use MultiSafepay\ConnectCore\Gateway\Request\Builder\RedirectTransactionBuilder;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use Magento\Sales\Model\Order\Payment\Transaction\Builder as PaymentTransactionBuilder;

class AbstractTransactionTestCase extends AbstractPaymentTestCase
{
    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param array $paymentData
     * @return TransactionInterface
     */
    protected function createTransaction(
        OrderInterface $order,
        OrderPaymentInterface $payment,
        string $transactionType,
        string $transactionId,
        array $paymentData = []
    ): TransactionInterface {

        $order->setPayment($payment);
        $order->save();

        $payment = $order->getPayment();
        $payment->setAdditionalInformation([Transaction::RAW_DETAILS => (array)$paymentData]);
        $payment->setParentTransactionId(null);
        $this->getPaymentRepository()->save($payment);

        $transaction = $this->buildTransaction(
            $payment,
            $order,
            $transactionType,
            (string)$transactionId,
            $paymentData
        );

        $this->getTransactionRepository()->save($transaction);
        $transactionId = (int)$transaction->getTransactionId();

        $transaction = $this->getTransactionRepository()->get($transactionId);
        $this->assertSame($order->getId(), $transaction->getOrderId());

        return $transaction;
    }

    /**
     * @param OrderPaymentInterface $payment
     * @param OrderInterface $order
     * @param string $transactionType
     * @param string $transactionId
     * @param array $rawDetails
     * @return TransactionInterface
     */
    protected function buildTransaction(
        OrderPaymentInterface $payment,
        OrderInterface $order,
        string $transactionType,
        string $transactionId,
        array $rawDetails
    ): TransactionInterface {
        $transactionBuilder = $this->getPaymentTransactionBuilder();
        $transaction = $transactionBuilder
            ->setPayment($payment)
            ->setOrder($order)
            ->setTransactionId($transactionId)
            ->setAdditionalInformation([Transaction::RAW_DETAILS => (array)$rawDetails])
            ->setFailSafe(true)
            ->build($transactionType);

        if (!$transaction) {
            $this->assertTrue(false, 'Transaction builder creates null, instead of transaction');
        }

        return $transaction;
    }

    /**
     * @return TransactionRepositoryInterface
     */
    protected function getTransactionRepository(): TransactionRepositoryInterface
    {
        return $this->getObjectManager()->get(TransactionRepositoryInterface::class);
    }

    /**
     * @return PaymentTransactionBuilder
     */
    protected function getPaymentTransactionBuilder(): PaymentTransactionBuilder
    {
        return $this->getObjectManager()->get(PaymentTransactionBuilder::class);
    }
}
