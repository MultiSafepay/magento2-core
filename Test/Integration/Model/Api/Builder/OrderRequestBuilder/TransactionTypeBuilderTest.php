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

namespace MultiSafepay\ConnectCore\Test\Integration\Model\Api\Builder\OrderRequestBuilder;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\TransactionTypeBuilder;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\BancontactConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\CreditCardConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\IdealConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\PayafterConfigProvider;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\Exception\InvalidArgumentException;

class TransactionTypeBuilderTest extends AbstractTestCase
{
    /**
     * @var Order
     */
    private $order;

    /**
     * @var Payment
     */
    private $payment;

    /**
     * @var OrderRequest
     */
    private $orderRequest;

    /**
     * @var TransactionTypeBuilder
     */
    private $transactionTypeBuilder;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->order = $this->getObjectManager()->create(Order::class);
        $this->payment = $this->getObjectManager()->create(Payment::class);
        $this->orderRequest = $this->getObjectManager()->create(OrderRequest::class);
        $this->transactionTypeBuilder = $this->getObjectManager()->create(TransactionTypeBuilder::class);
    }

    /**
     * @return void
     * @throws LocalizedException
     * @throws InvalidArgumentException
     */
    public function testWillBuildDirectTypeForPaymentComponentTransactionType()
    {
        $this->payment->setMethod(CreditCardConfigProvider::CODE);
        $this->payment->setAdditionalInformation(['transaction_type' => 'payment_component']);
        $this->transactionTypeBuilder->build($this->order, $this->payment, $this->orderRequest);

        $this->assertEquals(TransactionTypeBuilder::TRANSACTION_TYPE_DIRECT_VALUE, $this->orderRequest->getType());
    }

    /**
     * @magentoConfigFixture default_store payment/multisafepay_ideal/show_payment_page 1
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function testWillBuildRedirectTypeForIdealPaymentMethodWithPaymentPage()
    {
        $this->payment->setMethod(IdealConfigProvider::CODE);
        $this->transactionTypeBuilder->build($this->order, $this->payment, $this->orderRequest);

        $this->assertEquals(TransactionTypeBuilder::TRANSACTION_TYPE_REDIRECT_VALUE, $this->orderRequest->getType());
    }

    /**
     * @magentoConfigFixture default_store payment/multisafepay_payafter/transaction_type direct
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function testWillBuildConfiguredType()
    {
        $this->payment->setMethod(PayafterConfigProvider::CODE);
        $this->transactionTypeBuilder->build($this->order, $this->payment, $this->orderRequest);

        $this->assertEquals(TransactionTypeBuilder::TRANSACTION_TYPE_DIRECT_VALUE, $this->orderRequest->getType());
    }

    /**
     * @return void
     * @throws InvalidArgumentException
     */
    public function testWillBuildRedirectTypeAsFallback()
    {
        $this->payment->setMethod(BancontactConfigProvider::CODE);
        $this->transactionTypeBuilder->build($this->order, $this->payment, $this->orderRequest);

        $this->assertEquals(TransactionTypeBuilder::TRANSACTION_TYPE_REDIRECT_VALUE, $this->orderRequest->getType());
    }
}
