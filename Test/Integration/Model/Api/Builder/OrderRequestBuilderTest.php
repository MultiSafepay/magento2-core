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
 * Copyright © 2020 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Test\Integration\Model\Api\Builder;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\SalesRule\Model\Rule;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class OrderRequestBuilderTest extends AbstractTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/live_api_key livekey
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @throws LocalizedException
     */
    public function testSimpleOrder()
    {
        $order = $this->getOrder();
        $orderRequest = $this->getOrderRequestBuilder()->build($order);
        $data = $orderRequest->getData();

        $this->assertSame((float)$order->getGrandTotal(), (float)$data['amount'] / 100);
    }

    /**
     * @magentoConfigFixture default_store multisafepay/general/live_api_key livekey
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @throws LocalizedException
     * @throws Exception
     */
    public function testOrderWithDiscountCoupon()
    {
        $quote = $this->getQuote('tableRate');
        $items = $quote->getAllItems();
        $this->assertTrue(count($items) > 1);

        $couponCode = '1234567890';
        try {
            $this->createCouponCode($couponCode);
        } catch (\Magento\Framework\Exception\AlreadyExistsException $exception) {
        }

        $quote->setCouponCode($couponCode);

        $shippingRate = $this->getObjectManager()->get(\Magento\Quote\Model\Quote\Address\Rate::class);
        $shippingRate->setCode('freeshipping_freeshipping')
            ->setPrice(0);

        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod('flatrate_flatrate');
        $quote->getShippingAddress()->addShippingRate($shippingRate);

        $quote->setCustomerEmail('info@example.com');
        $quote->collectTotals();
        $quote->save();

        /** @var QuoteManagement $quoteManagement */
        $quoteManagement = $this->getObjectManager()->get(QuoteManagement::class);
        $order = $quoteManagement->submit($quote);
        $this->assertInstanceOf(OrderInterface::class, $order);

        $payment = $order->getPayment();
        $payment->setAdditionalInformation('transaction_type', 'direct');
        $order->setPayment($payment);

        $this->assertNotEmpty($order->getId());
        $this->assertNotEmpty($order->getIncrementId());

        $orderRequest = $this->getOrderRequestBuilder()->build($order);
        $data = $orderRequest->getData();

        $this->assertEquals((float)$order->getGrandTotal(), (float)$data['amount'] / 100);

        /*
        @todo: How is this currently being solved?
        $calculatedTotal = 0;
        foreach ($order->getItems() as $orderItem) {
            $calculatedTotal += $orderItem->getRowTotal();
        }

        $this->assertEquals($data['amount'], $calculatedTotal * 100);
        */
    }

    /**
     * @return OrderRequestBuilder
     */
    private function getOrderRequestBuilder(): OrderRequestBuilder
    {
        return $this->getObjectManager()->create(OrderRequestBuilder::class);
    }

    /**
     * @param string $couponCode
     */
    private function createCouponCode(string $couponCode)
    {
        $coupon['name'] = 'example';
        $coupon['desc'] = 'Coupon Code example.';
        $coupon['start'] = date('Y-m-d');
        $coupon['end'] = '';
        $coupon['max_redemptions'] = 1;
        $coupon['discount_type'] = 'by_percent';
        $coupon['discount_amount'] = 0.01;
        $coupon['flag_is_free_shipping'] = 'no';
        $coupon['redemptions'] = 1;
        $coupon['code'] = $couponCode;

        $shoppingCartPriceRule = $this->getObjectManager()->create(Rule::class);
        $shoppingCartPriceRule->setName($coupon['name'])
            ->setDescription($coupon['desc'])
            ->setFromDate($coupon['start'])
            ->setToDate($coupon['end'])
            ->setUsesPerCustomer($coupon['max_redemptions'])
            ->setCustomerGroupIds(['0', '1', '2', '3'])
            ->setIsActive(1)
            ->setSimpleAction($coupon['discount_type'])
            ->setDiscountAmount($coupon['discount_amount'])
            ->setDiscountQty(1)
            ->setApplyToShipping($coupon['flag_is_free_shipping'])
            ->setTimesUsed($coupon['redemptions'])
            ->setWebsiteIds(['1'])
            ->setCouponType(2)
            ->setCouponCode($coupon['code'])
            ->setUsesPerCoupon(null);
        $shoppingCartPriceRule->save();
    }
}
