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

namespace MultiSafepay\ConnectCore\Test\Integration\Model\Api\Builder\OrderRequestBuilder\GatewayInfoBuilder;

use Exception;
use Magento\Framework\Exception\LocalizedException;
// phpcs:ignore
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\GatewayInfoBuilder\WalletPaymentTokenGatewayInfoBuilder;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class WalletPaymentTokenGatewayInfoBuilderTest extends AbstractTestCase
{
    /**
     * @var WalletPaymentTokenGatewayInfoBuilder
     */
    private $walletPaymentTokenGatewayInfoBuilder;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->walletPaymentTokenGatewayInfoBuilder =
            $this->getObjectManager()->create(WalletPaymentTokenGatewayInfoBuilder::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     *
     * @throws LocalizedException
     * @throws Exception
     */
    public function testCreditCardPayloadBuild(): void
    {
        $token = 'test-token';
        $expectedResult = [
            'payment_token' => $token,
        ];
        $order = $this->getOrder();
        $payment = $order->getPayment();
        $payment->setAdditionalInformation(['payment_token' => $token]);

        self::assertSame(
            $expectedResult,
            $this->walletPaymentTokenGatewayInfoBuilder->build($order, $payment)->getData()
        );

        $payment->setAdditionalInformation([]);

        self::assertNotEquals(
            $expectedResult,
            $this->walletPaymentTokenGatewayInfoBuilder->build($order, $payment)->getData()
        );

        $payment->setAdditionalInformation(['payment_token' => null]);

        self::assertNotEquals(
            $expectedResult,
            $this->walletPaymentTokenGatewayInfoBuilder->build($order, $payment)->getData()
        );
    }
}
