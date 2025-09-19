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

namespace MultiSafepay\ConnectCore\Test\Integration\Model\Api\Builder\OrderRequestBuilder\AdditionalDataBuilder;

use Exception;
use Magento\Framework\Exception\LocalizedException;
// phpcs:ignore
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\AdditionalDataBuilder\PaymentComponentAdditionalDataBuilder;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class PaymentComponentAdditionalDataBuilderTest extends AbstractTestCase
{
    /**
     * @var PaymentComponentAdditionalDataBuilder
     */
    private $PaymentComponentAdditionalDataBuilder;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->PaymentComponentAdditionalDataBuilder =
            $this->getObjectManager()->create(PaymentComponentAdditionalDataBuilder::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     *
     * @throws LocalizedException
     * @throws Exception
     */
    public function testCreditCardPayloadBuild(): void
    {
        $payload = 'test_payload';
        $expectedResult = [
            'payment_data' => [
                'payload' => $payload,
            ],
        ];
        $order = $this->getOrder();
        $payment = $order->getPayment();
        $payment->setAdditionalInformation(['payload' => $payload]);

        self::assertSame($expectedResult, $this->PaymentComponentAdditionalDataBuilder->build($order, $payment));

        $payment->setAdditionalInformation([]);

        self::assertNotEquals($expectedResult, $this->PaymentComponentAdditionalDataBuilder->build($order, $payment));

        $payment->setAdditionalInformation(['payload' => null]);

        self::assertEmpty($this->PaymentComponentAdditionalDataBuilder->build($order, $payment));
    }
}
