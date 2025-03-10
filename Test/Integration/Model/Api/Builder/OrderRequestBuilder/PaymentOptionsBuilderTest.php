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

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\PaymentOptionsBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\PluginDataBuilder;
use MultiSafepay\ConnectCore\Model\SecureToken;
use MultiSafepay\ConnectCore\Test\Integration\Payment\AbstractTransactionTestCase;
use MultiSafepay\Exception\InvalidArgumentException;
use MultiSafepay\Exception\InvalidTotalAmountException;
use ReflectionException;
use ReflectionObject;

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
     * @throws InvalidArgumentException
     * @throws InvalidTotalAmountException
     * @throws Exception
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
}
