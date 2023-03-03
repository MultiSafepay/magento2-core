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

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\Method\Adapter as PaymentMethodAdapter;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class AbstractPaymentTestCase extends AbstractTestCase
{
    /**
     * @param string $paymentAdapterClass
     * @param Payment $payment
     * @param OrderInterface $order
     * @throws LocalizedException
     */
    protected function runPaymentAction(string $paymentAdapterClass, Payment $payment, OrderInterface $order)
    {
        $this->getAreaStateObject()->setAreaCode(Area::AREA_FRONTEND);

        /** @var PaymentMethodAdapter $paymentAdapter */
        $paymentAdapter = $this->getObjectManager()->get($paymentAdapterClass);
        $paymentAction = $paymentAdapter->getConfigData('payment_action');

        if ($paymentAction === 'authorize') {
            $paymentAdapter->authorize($payment, $order->getGrandTotal());
            return;
        }

        if ($paymentAction === 'initialize') {
            $stateObject = new DataObject();
            $paymentAdapter->setInfoInstance($payment);
            $paymentAdapter->setStore($order->getStoreId());
            $paymentAdapter->initialize($paymentAction, $stateObject);
            return;
        }
    }

    /**
     * @param string $method
     * @param string $type
     * @param string $issuerId
     * @return Payment
     * @throws LocalizedException
     */
    protected function getPayment(string $method, string $type, string $issuerId = ''): Payment
    {
        /** @var Payment $payment */
        $payment = $this->getObjectManager()->create(Payment::class);
        $payment->setMethod($method);
        $payment->setAdditionalInformation('transaction_type', $type);

        if ($issuerId) {
            $payment->setAdditionalInformation('issuer_id', $issuerId);
        }

        $payment->setOrder($this->getOrder());

        return $payment;
    }

    /**
     * @return OrderPaymentRepositoryInterface
     */
    protected function getPaymentRepository(): OrderPaymentRepositoryInterface
    {
        return $this->getObjectManager()->get(OrderPaymentRepositoryInterface::class);
    }

    /**
     * @return State
     */
    private function getAreaStateObject(): State
    {
        return $this->getObjectManager()->get(State::class);
    }
}
