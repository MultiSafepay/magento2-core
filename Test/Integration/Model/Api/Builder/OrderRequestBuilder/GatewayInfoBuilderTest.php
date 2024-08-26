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
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\GatewayInfoBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\PluginDataBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\TransactionTypeBuilder;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\AfterpayConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\DirectBankTransferConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\DirectDebitConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\EinvoicingConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\IdealConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\VisaConfigProvider;
use MultiSafepay\ConnectCore\Test\Integration\Payment\AbstractTransactionTestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GatewayInfoBuilderTest extends AbstractTransactionTestCase
{
    /**
     * @var GatewayInfoBuilder
     */
    private $gatewayInfoBuilder;

    /**
     * @var PluginDataBuilder
     */
    private $pluginDetailsBuilder;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->gatewayInfoBuilder = $this->getObjectManager()->create(GatewayInfoBuilder::class);
        $this->pluginDetailsBuilder = $this->getObjectManager()->create(PluginDataBuilder::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @dataProvider         gatewaysDataProvider
     *
     * @param string $paymentCode
     * @param array $additionalData
     * @param bool $expected
     * @throws LocalizedException
     */
    public function testGatewayInfoBuildersWithDifferentPaymentMethods(
        string $paymentCode,
        array $additionalData,
        bool $expected
    ): void {
        $payment = $this->getPayment($paymentCode, $additionalData['transaction_type']);
        $payment->setAdditionalInformation($additionalData);
        $order = $this->getObjectManager()->get(OrderInterfaceFactory::class)->create()->loadByIncrementId('100000001');
        $orderRequestData = $this->getBuildedOrderRequest($order, $payment)->getData();

        self::assertEquals($expected, isset($orderRequestData['gateway_info']));

        if ($expected) {
            foreach ($orderRequestData['gateway_info'] as $key => $item) {
                if (isset($additionalData[$key])) {
                    self::assertEquals($additionalData[$key], $item);
                }

                if ($key === 'birthday') {
                    self::assertEquals(date('Y-m-d', strtotime($additionalData['date_of_birth'])), $item);
                }

                if ($key === 'phone') {
                    self::assertEquals(
                        ($paymentCode === EinvoicingConfigProvider::CODE
                            ? $order->getBillingAddress()->getTelephone()
                            : $additionalData['phone_number']),
                        $item
                    );
                }
            }

            if ($paymentCode === AfterpayConfigProvider::CODE) {
                self::assertArrayHasKey('email', $orderRequestData['gateway_info']);
            }
        }
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     *
     * @throws LocalizedException
     */
    public function testGatewayInfoBuildersWithMissedPhoneNumberForPaymentMethods(): void
    {
        $payment = $this->getPayment(
            EinvoicingConfigProvider::CODE,
            TransactionTypeBuilder::TRANSACTION_TYPE_DIRECT_VALUE
        );
        $payment->setAdditionalInformation(
            [
                'date_of_birth' => '10-10-1990',
                'account_number' => 'NL87ABNA0000000004',
                'gender' => 'mr'
            ]
        );
        $order = $this->getObjectManager()->get(OrderInterfaceFactory::class)->create()->loadByIncrementId('100000001');
        $order->getBillingAddress()->setTelephone(null);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('This payment gateway requires a valid telephone number');

        $this->getBuildedOrderRequest($order, $payment);

        $payment = $this->getPayment(
            AfterpayConfigProvider::CODE,
            TransactionTypeBuilder::TRANSACTION_TYPE_DIRECT_VALUE
        );

        $this->getBuildedOrderRequest($order, $payment);
    }

    /**
     * @return array[]
     */
    public function gatewaysDataProvider(): array
    {
        return [
            [
                IdealConfigProvider::CODE,
                [
                    'transaction_type' => TransactionTypeBuilder::TRANSACTION_TYPE_DIRECT_VALUE
                ],
                false
            ],
            [
                EinvoicingConfigProvider::CODE,
                [
                    'date_of_birth' => '10-10-1990',
                    'account_number' => 'NL87ABNA0000000004',
                    'transaction_type' => TransactionTypeBuilder::TRANSACTION_TYPE_DIRECT_VALUE
                ],
                true
            ],
            [
                AfterpayConfigProvider::CODE,
                [
                    'date_of_birth' => '10-10-1990',
                    'gender' => 'mr',
                    'phone_number' => '12314566',
                    'transaction_type' => TransactionTypeBuilder::TRANSACTION_TYPE_DIRECT_VALUE
                ],
                true
            ],
            [
                DirectBankTransferConfigProvider::CODE,
                [
                    'account_holder_iban' => 'NL87ABNA0000000002',
                    'account_id' => '123',
                    'account_holder_name' => 'Test',
                    'account_holder_city' => 'Amsterdam',
                    'account_holder_country' => 'NL',
                    'account_holder_bic' => '123456',
                    'transaction_type' => TransactionTypeBuilder::TRANSACTION_TYPE_DIRECT_VALUE
                ],
                true
            ],
            [
                DirectDebitConfigProvider::CODE,
                [
                    'account_holder_iban' => 'NLEQ87ABNA0000000002',
                    'account_holder_name' => 'Test',
                    'transaction_type' => TransactionTypeBuilder::TRANSACTION_TYPE_DIRECT_VALUE
                ],
                true
            ],
            [
                VisaConfigProvider::CODE,
                ['transaction_type' => TransactionTypeBuilder::TRANSACTION_TYPE_DIRECT_VALUE],
                false
            ]
        ];
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @return OrderRequest
     * @throws LocalizedException
     */
    private function getBuildedOrderRequest(
        OrderInterface $order,
        OrderPaymentInterface $payment
    ): OrderRequest {
        $orderRequest = $this->getObjectManager()->create(OrderRequest::class);
        $this->pluginDetailsBuilder->build($order, $payment, $orderRequest);
        $this->gatewayInfoBuilder->build($order, $payment, $orderRequest);

        return $orderRequest;
    }
}
