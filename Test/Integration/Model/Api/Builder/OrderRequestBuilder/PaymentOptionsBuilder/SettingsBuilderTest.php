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

namespace MultiSafepay\ConnectCore\Test\Integration\Model\Api\Builder\OrderRequestBuilder\PaymentOptionsBuilder;

use Magento\Payment\Gateway\Config\Config;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\PaymentOptionsBuilder\SettingsBuilder;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\BancontactConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\IdealConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Giftcard\EdenredGiftcardConfigProvider;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class SettingsBuilderTest extends AbstractTestCase
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
     * @var SettingsBuilder
     */
    private $settingsBuilder;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->order = $this->getObjectManager()->create(Order::class);
        $this->payment = $this->getObjectManager()->create(Payment::class);
        $this->orderRequest = $this->getObjectManager()->create(OrderRequest::class);
        $this->settingsBuilder = $this->getObjectManager()->create(SettingsBuilder::class);
    }

    /**
     * @return void
     */
    public function testBuildWillReturnEmptyArrayIfPaymentMethodHasNoSettings()
    {
        $this->payment->setMethod(BancontactConfigProvider::CODE);
        $this->assertEquals([], $this->settingsBuilder->build($this->order, $this->payment, $this->orderRequest));
    }

    /**
     * @magentoConfigFixture default_store payment/multisafepay_ideal/show_payment_page 1
     *
     * @return void
     */
    public function testBuildWillReturnSettingsForIdealPaymentMethod()
    {
        $this->payment->setMethod(IdealConfigProvider::CODE);

        $expectedSettings = [
            'gateways' => [
                'IDEAL' => [
                    'show_pre' => true
                ]
            ]
        ];

        $this->assertEquals(
            $expectedSettings,
            $this->settingsBuilder->build($this->order, $this->payment, $this->orderRequest)
        );
    }

    /**
     * @return void
     */
    public function testBuildWillReturnSettingsForEdenRedPaymentMethodWithCoupons()
    {
        $this->payment->setMethod(EdenredGiftcardConfigProvider::CODE);

        $edenredGiftcardConfigProvider = $this->getMockBuilder(EdenredGiftcardConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $edenredGiftcardConfigProvider
            ->method('getAvailableCouponsByOrder')
            ->with($this->order)
            ->willReturn(['COUPON1', 'COUPON2']);

        $settingsBuilder = $this->getMockBuilder(SettingsBuilder::class)
            ->setConstructorArgs([$this->getObjectManager()->create(Config::class), $edenredGiftcardConfigProvider])
            ->setMethodsExcept(['build'])
            ->getMock();

        $expectedSettings = [
            'gateways' => [
                'coupons' => [
                    'allow' => ['COUPON1', 'COUPON2'],
                    'disabled' => false,
                ],
            ],
        ];

        $this->assertEquals(
            $expectedSettings,
            $settingsBuilder->build($this->order, $this->payment, $this->orderRequest)
        );
    }

    /**
     * @return void
     */
    public function testBuildWillReturnSettingsForEdenRedPaymentMethodWithoutCoupons()
    {
        $this->payment->setMethod(EdenredGiftcardConfigProvider::CODE);

        $expectedSettings = [
            'gateways' => [
                'coupons' => [
                    'allow' => [],
                    'disabled' => true,
                ],
            ],
        ];

        $this->assertEquals(
            $expectedSettings,
            $this->settingsBuilder->build($this->order, $this->payment, $this->orderRequest)
        );
    }
}
