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

namespace MultiSafepay\ConnectCore\Test\Integration\Service\Order;

use Magento\Framework\Exception\LocalizedException;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\AfterpayConfigProvider;
use MultiSafepay\ConnectCore\Service\Order\ProcessChangePaymentMethod;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use ReflectionException;
use ReflectionObject;

class ProcessChangePaymentMethodTest extends AbstractTestCase
{
    /**
     * @var ProcessChangePaymentMethod
     */
    private $processChangePaymentMethod;

    /**
     * @var array
     */
    private $transactionData;

    /**
     * @var ReflectionObject
     */
    private $processChangePaymentMethodReflector;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->processChangePaymentMethod = $this->getObjectManager()->get(ProcessChangePaymentMethod::class);
        $this->transactionData = $this->getManualCaptureTransactionData() ?? [];
        $this->processChangePaymentMethodReflector
            = new ReflectionObject($this->processChangePaymentMethod);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @throws LocalizedException
     * @throws ReflectionException
     */
    public function testProcessChangePaymentMethod(): void
    {
        $order = $this->getOrderWithVisaPaymentMethod();
        $payment = $order->getPayment();
        $gatewayCode = $payment->getMethodInstance()->getConfigData('gateway_code');
        $transactionType = 'AFTERPAY';
        $canChangePaymentMethod = $this->processChangePaymentMethodReflector->getMethod('canChangePaymentMethod');
        $canChangePaymentMethod->setAccessible(true);

        self::assertTrue(
            $canChangePaymentMethod->invoke(
                $this->processChangePaymentMethod,
                $transactionType,
                $gatewayCode,
                $order
            )
        );

        $this->processChangePaymentMethod->execute(
            $order,
            $payment,
            $transactionType,
            $payment->getMethod(),
            $this->transactionData
        );

        self::assertFalse(
            $canChangePaymentMethod->invoke(
                $this->processChangePaymentMethod,
                AfterpayConfigProvider::CODE,
                $payment->getMethod(),
                $order
            )
        );

        self::assertEquals(AfterpayConfigProvider::CODE, $payment->getMethod());
    }
}
