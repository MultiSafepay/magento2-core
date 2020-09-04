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

namespace MultiSafepay\ConnectCore\Test\Integration\Gateway\Request;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use MultiSafepay\Api\Transactions\RefundRequest;
use MultiSafepay\ConnectCore\Gateway\Request\Builder\RefundTransactionBuilder;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;

class RefundTransactionBuilderTest extends AbstractTestCase
{
    /**
     * Test to see if this could be build
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws LocalizedException
     */
    public function testBuild()
    {
        /** @var RefundTransactionBuilder $refundTransactionBuilder */
        $refundTransactionBuilder = $this->getObjectManager()->get(RefundTransactionBuilder::class);

        $stateObject = new DataObject();
        $order = $this->getOrder();

        /** @var PaymentDataObjectFactoryInterface $paymentDataObjectFactory */
        $paymentDataObjectFactory = $this->getObjectManager()->get(PaymentDataObjectFactoryInterface::class);
        $paymentDataObject = $paymentDataObjectFactory->create($order->getPayment());

        $buildSubject = [
            'stateObject' => $stateObject,
            'payment' => $paymentDataObject,
            'amount' => $order->getGrandTotal(),
            'currency' => $order->getOrderCurrencyCode()
        ];

        $return = $refundTransactionBuilder->build($buildSubject);

        $this->assertArrayHasKey('payload', $return);
        $this->assertArrayHasKey('order_id', $return);
        $this->assertInstanceOf(RefundRequest::class, $return['payload']);
    }
}
