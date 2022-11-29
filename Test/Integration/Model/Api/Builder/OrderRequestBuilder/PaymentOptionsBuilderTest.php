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
 * Copyright © 2022 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Test\Integration\Model\Api\Builder\OrderRequestBuilder;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PaymentOptions;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\PaymentOptionsBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\PluginDataBuilder;
use MultiSafepay\ConnectCore\Model\SecureToken;
use MultiSafepay\ConnectCore\Model\Ui\Giftcard\EdenredGiftcardConfigProvider;
use MultiSafepay\ConnectCore\Test\Integration\Payment\AbstractTransactionTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use ReflectionObject;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PaymentOptionsBuilderTest extends AbstractTransactionTestCase
{
    /**
     * @var PaymentOptionsBuilder
     */
    private $paymentOptionsBuilder;

    /**
     * @var PluginDataBuilder
     */
    private $pluginDetailsBuilder;

    /**
     * @var ReflectionObject
     */
    private $paymentOptionsBuilderReflector;

    /**
     * @var SecureToken
     */
    private $secureToken;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->paymentOptionsBuilder = $this->getObjectManager()->create(PaymentOptionsBuilder::class);
        $this->pluginDetailsBuilder = $this->getObjectManager()->create(PluginDataBuilder::class);
        $this->secureToken = $this->getObjectManager()->create(SecureToken::class);
        $this->paymentOptionsBuilderReflector = new ReflectionObject($this->paymentOptionsBuilder);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws ReflectionException
     */
    public function testBuildPaymentOptionsBuilder(): void
    {
        $orderRequest = $this->getObjectManager()->create(OrderRequest::class);
        $order = $this->getOrderWithVisaPaymentMethod();
        $storeId = $order->getStoreId();
        $payment = $order->getPayment();
        $this->pluginDetailsBuilder->build($order, $payment, $orderRequest);
        $this->paymentOptionsBuilder->build($order, $payment, $orderRequest);
        $orderRequestData = $orderRequest->getData();
        $params = [
            'secureToken' => $this->secureToken->generate((string)$order->getRealOrderId()),
        ];
        $getUrlMetod = $this->paymentOptionsBuilderReflector->getMethod('getUrl');
        $getUrlMetod->setAccessible(true);

        self::assertArrayHasKey('payment_options', $orderRequestData);
        self::assertNotEmpty($orderRequestData['payment_options']['notification_url']);
        self::assertNotEmpty($orderRequestData['payment_options']['cancel_url']);
        self::assertNotEmpty($orderRequestData['payment_options']['notification_method']);
        self::assertNotEmpty($orderRequestData['payment_options']['redirect_url']);
        self::assertEmpty($orderRequestData['payment_options']['settings']);
        self::assertEquals(
            $getUrlMetod->invoke(
                $this->paymentOptionsBuilder,
                PaymentOptionsBuilder::NOTIFICATION_URL . '?store_id=1',
                $storeId
            ),
            $orderRequestData['payment_options']['notification_url']
        );
        self::assertEquals(
            $getUrlMetod->invoke(
                $this->paymentOptionsBuilder,
                PaymentOptionsBuilder::REDIRECT_URL,
                $storeId,
                $params
            ),
            $orderRequestData['payment_options']['redirect_url']
        );
        self::assertEquals(
            $getUrlMetod->invoke(
                $this->paymentOptionsBuilder,
                PaymentOptionsBuilder::CANCEL_URL,
                $storeId,
                $params
            ),
            $orderRequestData['payment_options']['cancel_url']
        );
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function testBuildEdenredPaymentOptionsWithCoupons(): void
    {
        $orderRequest = $this->getObjectManager()->create(OrderRequest::class);
        $order = $this->getOrderWithVisaPaymentMethod();
        $payment = $order->getPayment();
        $payment->setMethod(EdenredGiftcardConfigProvider::CODE);
        $this->pluginDetailsBuilder->build($order, $payment, $orderRequest);
        $this->getMockBuilder(PaymentOptionsBuilder::class)
            ->setConstructorArgs([
                $this->getObjectManager()->get(PaymentOptions::class),
                $this->getObjectManager()->get(SecureToken::class),
                $this->getObjectManager()->get(StoreManagerInterface::class),
                $this->getEdenredGiftcardConfigProviderMock(
                    $order,
                    [
                        EdenredGiftcardConfigProvider::EDENCOM_COUPON_CODE,
                        EdenredGiftcardConfigProvider::EDENECO_COUPON_CODE,
                    ]
                ),
            ])
            ->setMethodsExcept(['build'])
            ->getMock()
            ->build($order, $payment, $orderRequest);
        $orderRequestData = $orderRequest->getData();

        self::assertArrayHasKey('payment_options', $orderRequestData);
        self::assertEquals(
            [
                'gateways' => [
                    'coupons' => [
                        'allow' => [
                            strtoupper(EdenredGiftcardConfigProvider::EDENCOM_COUPON_CODE),
                            strtoupper(EdenredGiftcardConfigProvider::EDENECO_COUPON_CODE),
                        ],
                        'disabled' => false,
                    ],
                ],
            ],
            $orderRequestData['payment_options']['settings']
        );

        $this->getMockBuilder(PaymentOptionsBuilder::class)
            ->setConstructorArgs([
                $this->getObjectManager()->get(PaymentOptions::class),
                $this->getObjectManager()->get(SecureToken::class),
                $this->getObjectManager()->get(StoreManagerInterface::class),
                $this->getEdenredGiftcardConfigProviderMock(
                    $order,
                    [
                        EdenredGiftcardConfigProvider::EDENCOM_COUPON_CODE,
                    ]
                ),
            ])
            ->setMethodsExcept(['build'])
            ->getMock()
            ->build($order, $payment, $orderRequest);

        self::assertEquals(
            $orderRequest->getGatewayCode(),
            strtoupper(EdenredGiftcardConfigProvider::EDENCOM_COUPON_CODE)
        );

        $this->getMockBuilder(PaymentOptionsBuilder::class)
            ->setConstructorArgs([
                $this->getObjectManager()->get(PaymentOptions::class),
                $this->getObjectManager()->get(SecureToken::class),
                $this->getObjectManager()->get(StoreManagerInterface::class),
                $this->getEdenredGiftcardConfigProviderMock($order, []),
            ])
            ->setMethodsExcept(['build'])
            ->getMock()
            ->build($order, $payment, $orderRequest);

        self::assertEquals(
            [
                'gateways' => [
                    'coupons' => [
                        'allow' => [],
                        'disabled' => true,
                    ],
                ],
            ],
            $orderRequest->getData()['payment_options']['settings']
        );
    }

    /**
     * @param OrderInterface $order
     * @param array $coupons
     * @return MockObject
     */
    private function getEdenredGiftcardConfigProviderMock(
        OrderInterface $order,
        array $coupons
    ): MockObject {
        $edenredGiftcardConfigProvider = $this->getMockBuilder(EdenredGiftcardConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $edenredGiftcardConfigProvider
            ->method('getAvailableCouponsByOrder')
            ->with($order)
            ->willReturn($coupons);

        return $edenredGiftcardConfigProvider;
    }
}
