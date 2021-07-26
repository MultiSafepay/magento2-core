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

namespace MultiSafepay\ConnectCore\Test\Integration\Model\Api\Builder\OrderRequestBuilder;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\AdditionalDataBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\PluginDataBuilder;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\CreditCardConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\IdealConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\MaestroConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\VisaConfigProvider;
use MultiSafepay\ConnectCore\Test\Integration\Payment\AbstractTransactionTestCase;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\TransactionTypeBuilder;

class AdditionalDataBuilderTest extends AbstractTransactionTestCase
{
    /**
     * @var AdditionalDataBuilder
     */
    private $additionalDataBuilder;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->additionalDataBuilder = $this->getObjectManager()->create(AdditionalDataBuilder::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @dataProvider         gatewaysDataProvider
     *
     * @param string $paymentCode
     * @param array $additionalData
     * @param array $expected
     * @throws LocalizedException
     */
    public function testAdditionalDataBuildersWithDifferentPaymentMethods(
        string $paymentCode,
        array $additionalData,
        array $expected
    ): void {
        $payment = $this->getPayment($paymentCode, TransactionTypeBuilder::TRANSACTION_TYPE_DIRECT_VALUE);
        $payment->setAdditionalInformation($additionalData);
        $order = $this->getObjectManager()->get(OrderInterfaceFactory::class)->create()->loadByIncrementId('100000001');
        $orderRequestData = $this->getBuildedOrderRequest($order, $payment)->getData();

        if (!$expected) {
            self::assertNotTrue(isset($orderRequestData['payment_data']));
        } else {
            self::assertEquals($expected, $orderRequestData['payment_data']);
        }
    }

    /**
     * @return array[]
     */
    public function gatewaysDataProvider(): array
    {
        return [
            [
                VisaConfigProvider::CODE,
                [
                    'payload' => 'test_payload',
                ],
                [
                    'payload' => 'test_payload',
                ],
            ],
            [
                CreditCardConfigProvider::CODE,
                [
                    'payload' => '',
                ],
                [],
            ],
            [
                MaestroConfigProvider::CODE,
                [
                    'test' => 'testAdditionalData',
                ],
                [],
            ],
            [
                IdealConfigProvider::CODE,
                [
                    'test' => 'testAdditionalData',
                ],
                [],
            ],
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
        $this->getObjectManager()->create(PluginDataBuilder::class)->build($order, $payment, $orderRequest);
        $this->additionalDataBuilder->build($order, $payment, $orderRequest);

        return $orderRequest;
    }
}
