<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * Copyright © MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Test\Integration\Service\Process;

use Exception;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\ApplePayConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\GooglePayConfigProvider;
use MultiSafepay\ConnectCore\Service\Process\AddWalletInformation;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class AddWalletInformationTest extends AbstractTestCase
{
    /**
     * @var AddWalletInformation
     */
    private $addWalletInformation;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        $objectManager = $this->getObjectManager();

        $this->logger = $this->createMock(Logger::class);

        $this->addWalletInformation = $objectManager->create(
            AddWalletInformation::class,
            [
                'logger' => $this->logger
            ]
        );
    }

    /**
     * Test execute when order payment is null.
     *
     * @throws Exception
     */
    public function testExecuteWithNullPayment(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getIncrementId')->willReturn('100000001');
        $order->method('getPayment')->willReturn(null);

        $transaction = [
            'payment_details' => [
                'type' => 'VISA'
            ]
        ];

        $this->logger->expects($this->once())
            ->method('logInfoForNotification')
            ->with(
                '100000001',
                'Payment could not be found when trying to add wallet information',
                $transaction
            );

        $result = $this->addWalletInformation->execute($order, $transaction);

        $this->assertTrue($result[StatusOperationInterface::SUCCESS_PARAMETER]);
    }

    /**
     * Test execute when payment method is not a wallet method.
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @dataProvider nonWalletPaymentMethodDataProvider
     *
     * @throws Exception
     */
    public function testExecuteWithNonWalletPaymentMethod(string $paymentMethod): void
    {
        /** @var Order $order */
        $order = $this->getOrder();
        $order->getPayment()->setMethod($paymentMethod);

        $transaction = [
            'payment_details' => [
                'type' => 'VISA'
            ]
        ];

        $this->logger->expects($this->once())
            ->method('logInfoForNotification')
            ->with(
                $order->getIncrementId(),
                'Payment method is not a wallet method, no need to add wallet information',
                $transaction
            );

        $result = $this->addWalletInformation->execute($order, $transaction);

        $this->assertTrue($result[StatusOperationInterface::SUCCESS_PARAMETER]);
        $this->assertNull(
            $order->getPayment()->getAdditionalInformation(
                AddWalletInformation::WALLET_CARD_TYPE_ADDITIONAL_DATA_PARAM_NAME
            )
        );
    }

    /**
     * @return array
     */
    public static function nonWalletPaymentMethodDataProvider(): array
    {
        return [
            'checkmo' => ['checkmo'],
            'ideal' => ['multisafepay_ideal'],
            'creditcard' => ['multisafepay_creditcard'],
        ];
    }

    /**
     * Test execute when wallet payment method is used but payment details are missing.
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @dataProvider walletPaymentMethodDataProvider
     *
     * @throws Exception
     */
    public function testExecuteWithWalletPaymentMethodAndMissingPaymentDetails(string $paymentMethod): void
    {
        /** @var Order $order */
        $order = $this->getOrder();
        $order->getPayment()->setMethod($paymentMethod);

        $transaction = [];

        $this->logger->expects($this->once())
            ->method('logInfoForNotification')
            ->with(
                $order->getIncrementId(),
                'Wallet information was not added, payment details not found',
                $transaction
            );

        $result = $this->addWalletInformation->execute($order, $transaction);

        $this->assertTrue($result[StatusOperationInterface::SUCCESS_PARAMETER]);
        $this->assertNull(
            $order->getPayment()->getAdditionalInformation(
                AddWalletInformation::WALLET_CARD_TYPE_ADDITIONAL_DATA_PARAM_NAME
            )
        );
    }

    /**
     * Test execute when wallet payment method is used and wallet information is added.
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @dataProvider walletPaymentInformationDataProvider
     *
     * @throws Exception
     */
    public function testExecuteWithWalletPaymentMethod(
        string $paymentMethod,
        array $transaction,
        string $expectedWalletType
    ): void {
        /** @var Order $order */
        $order = $this->getOrder();
        $order->getPayment()->setMethod($paymentMethod);

        $this->logger->expects($this->once())
            ->method('logInfoForNotification')
            ->with(
                $order->getIncrementId(),
                'Wallet information added to the payment additional information',
                $transaction
            );

        $result = $this->addWalletInformation->execute($order, $transaction);

        $this->assertTrue($result[StatusOperationInterface::SUCCESS_PARAMETER]);
        $this->assertEquals(
            $expectedWalletType,
            $order->getPayment()->getAdditionalInformation(
                AddWalletInformation::WALLET_CARD_TYPE_ADDITIONAL_DATA_PARAM_NAME
            )
        );
    }

    /**
     * @return array
     */
    public static function walletPaymentMethodDataProvider(): array
    {
        return [
            'googlepay' => [GooglePayConfigProvider::CODE],
            'applepay' => [ApplePayConfigProvider::CODE],
        ];
    }

    /**
     * @return array
     */
    public static function walletPaymentInformationDataProvider(): array
    {
        return [
            'googlepay visa' => [
                GooglePayConfigProvider::CODE,
                [
                    'payment_details' => [
                        'type' => 'VISA'
                    ]
                ],
                'VISA'
            ],
            'applepay mastercard' => [
                ApplePayConfigProvider::CODE,
                [
                    'payment_details' => [
                        'type' => 'MASTERCARD'
                    ]
                ],
                'MASTERCARD'
            ],
            'googlepay unknown type' => [
                GooglePayConfigProvider::CODE,
                [
                    'payment_details' => []
                ],
                'unknown'
            ],
        ];
    }
}
