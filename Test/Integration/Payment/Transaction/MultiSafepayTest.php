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

namespace MultiSafepay\ConnectCore\Test\Integration\Transaction;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\Method\Adapter as PaymentMethodAdapter;
use Magento\Sales\Model\Order\Payment\Transaction;
use MultiSafepay\ConnectCore\Test\Integration\Payment\AbstractTransactionTestCase;

class MultiSafepayTest extends AbstractTransactionTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws LocalizedException
     */
    public function testAuthorize()
    {
        $paymentData = ['some_id' => '@todo'];

        //$payment = $this->getPayment('ideal', 'direct', '0031');
        $payment = $this->getPayment('multisafepay', 'redirect');
        $payment->setLastTransId($paymentData['some_id']);
        $payment->setTransactionId($paymentData['some_id']);

        $order = $this->getOrder();
        $transaction = $this->createTransaction(
            $order,
            $payment,
            Transaction::TYPE_AUTH,
            (string)$paymentData['some_id'],
            $paymentData
        );

        /** @var PaymentMethodAdapter $paymentAdapter */
        $paymentAdapter = $this->getObjectManager()->get('MultiSafepayFacade');
        $payment = $this->getPaymentRepository()->get($transaction->getPaymentId());
        $paymentAdapter->authorize($payment, $order->getGrandTotal());
    }
}
