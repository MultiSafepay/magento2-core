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
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Store\Model\ScopeInterface;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\PluginDataBuilder;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\CreditCardConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\IdealConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\MaestroConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\VisaConfigProvider;
use MultiSafepay\ConnectCore\Test\Integration\Payment\AbstractTransactionTestCase;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\TransactionTypeBuilder;
use MultiSafepay\ConnectCore\Util\CaptureUtil;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\CaptureBuilder;
use Magento\TestFramework\App\MutableScopeConfig;
use MultiSafepay\Exception\InvalidArgumentException;
use MultiSafepay\Exception\InvalidTotalAmountException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CaptureBuilderTest extends AbstractTransactionTestCase
{
    /**
     * @var CaptureBuilder
     */
    private $captureBuilder;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->captureBuilder = $this->getObjectManager()->create(CaptureBuilder::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @dataProvider         gatewaysDataProvider
     * @magentoConfigFixture default_store payment/multisafepay_visa/manual_capture 1
     * @magentoConfigFixture default_store payment/multisafepay_creditcard/manual_capture 1
     * @magentoConfigFixture default_store payment/multisafepay_maestro/manual_capture 1
     *
     * @param string $paymentCode
     * @param string $paymentAction
     * @param bool $expected
     * @throws LocalizedException
     * @throws InvalidArgumentException
     * @throws InvalidTotalAmountException
     */
    public function testCaptureBuilderWithDifferentPaymentMethods(
        string $paymentCode,
        string $paymentAction,
        bool $expected
    ): void {
        $payment = $this->getPayment($paymentCode, TransactionTypeBuilder::TRANSACTION_TYPE_REDIRECT_VALUE);
        $order = $this->getOrder();
        $this->updatePaymentActionConfig($paymentCode, $paymentAction);
        $orderRequestData = $this->getBuiltOrderRequest($order, $payment)->getData();

        if (!$expected) {
            self::assertNotTrue(isset($orderRequestData['capture']));
        } else {
            self::assertEquals(CaptureUtil::CAPTURE_TRANSACTION_TYPE_MANUAL, $orderRequestData['capture']);
        }
    }

    /**
     * @param string $paymentCode
     * @param string $value
     */
    private function updatePaymentActionConfig(string $paymentCode, string $value): void
    {
        $this->getObjectManager()->get(MutableScopeConfig::class)->setValue(
            'payment/' . $paymentCode . '/payment_action',
            $value,
            ScopeInterface::SCOPE_STORE,
            'default'
        );
    }

    /**
     * @return array[]
     */
    public function gatewaysDataProvider(): array
    {
        return [
            [
                VisaConfigProvider::CODE,
                CaptureUtil::PAYMENT_ACTION_AUTHORIZE_ONLY,
                true
            ],
            [
                CreditCardConfigProvider::CODE,
                CaptureUtil::PAYMENT_ACTION_AUTHORIZE_AND_CAPTURE,
                false
            ],
            [
                MaestroConfigProvider::CODE,
                CaptureUtil::PAYMENT_ACTION_AUTHORIZE_ONLY,
                true
            ],
            [
                IdealConfigProvider::CODE,
                CaptureUtil::PAYMENT_ACTION_AUTHORIZE_AND_CAPTURE,
                false
            ],
        ];
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @return OrderRequest
     * @throws LocalizedException
     */
    private function getBuiltOrderRequest(
        OrderInterface $order,
        OrderPaymentInterface $payment
    ): OrderRequest {
        $orderRequest = $this->getObjectManager()->create(OrderRequest::class);
        $this->getObjectManager()->create(PluginDataBuilder::class)->build($order, $payment, $orderRequest);
        $this->captureBuilder->build($order, $payment, $orderRequest);

        return $orderRequest;
    }
}
