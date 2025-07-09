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
use Magento\Framework\Locale\ResolverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\CustomerBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\PluginDataBuilder;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\VisaConfigProvider;
use MultiSafepay\ConnectCore\Test\Integration\Payment\AbstractTransactionTestCase;

class CustomerBuilderTest extends AbstractTransactionTestCase
{
    /**
     * @var CustomerBuilder
     */
    private $customerBuilder;

    /**
     * @var PluginDataBuilder
     */
    private $pluginDetailsBuilder;

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->customerBuilder = $this->getObjectManager()->create(CustomerBuilder::class);
        $this->pluginDetailsBuilder = $this->getObjectManager()->create(PluginDataBuilder::class);
        $this->localeResolver = $this->getObjectManager()->create(ResolverInterface::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/address.php
     * @magentoDataFixture   Magento/Customer/_files/customer.php
     * @magentoDataFixture   Magento/Catalog/_files/product_simple.php
     *
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws Exception
     */
    public function testCustomerBuilderWithLocaleIsEmptyString(): void
    {
        $this->includeFixtureFile('order_without_store_id');

        $orderRequest = $this->getBuiltOrderRequest(
            $this->getOrder(),
            $this->getPayment(VisaConfigProvider::CODE, 'redirect')
        );

        self::assertEquals('', $orderRequest->getData()['customer']['locale']);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     *
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws Exception
     */
    public function testCustomerBuilder(): void
    {
        $order = $this->getOrder();
        $orderRequest = $this->getBuiltOrderRequest(
            $order,
            $this->getPayment(VisaConfigProvider::CODE, 'redirect')
        )->getData();

        $billingAddress = $order->getBillingAddress();

        self::assertEquals($orderRequest['customer']['firstname'], $billingAddress->getFirstname());
        self::assertEquals($orderRequest['customer']['lastname'], $billingAddress->getLastname());
        self::assertEquals($orderRequest['customer']['locale'], $this->localeResolver->emulate($order->getStoreId()));
        self::assertEquals($orderRequest['customer']['phone'], $billingAddress->getTelephone());
        self::assertEquals($orderRequest['customer']['email'], $billingAddress->getEmail());
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @return OrderRequest
     * @throws NoSuchEntityException
     */
    private function getBuiltOrderRequest(
        OrderInterface $order,
        OrderPaymentInterface $payment
    ): OrderRequest {
        $orderRequest = $this->getObjectManager()->create(OrderRequest::class);
        $this->pluginDetailsBuilder->build($order, $payment, $orderRequest);
        $this->customerBuilder->build($order, $payment, $orderRequest);

        return $orderRequest;
    }
}
