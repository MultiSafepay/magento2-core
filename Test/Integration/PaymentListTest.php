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

namespace MultiSafepay\ConnectCore\Test\Integration;

use Exception;
use Magento\Payment\Api\Data\PaymentMethodInterface;
use Magento\Payment\Api\PaymentMethodListInterface;

class PaymentListTest extends AbstractTestCase
{
    /**
     * Test to see if the `payment.xml` file is properly working
     */
    public function testPaymentMethodsExist()
    {
        $this->assertSame('multisafepay', $this->getPaymentMethod('multisafepay')->getCode());
        $this->assertSame('multisafepay_ideal', $this->getPaymentMethod('multisafepay_ideal')->getCode());
    }

    /**
     * @return PaymentMethodInterface
     * @throws Exception
     */
    private function getPaymentMethod(string $paymentMethodCode): PaymentMethodInterface
    {
        /** @var PaymentMethodListInterface $paymentMethodList */
        $paymentMethodList = $this->getObjectManager()->get(PaymentMethodListInterface::class);
        foreach ($paymentMethodList->getList(0) as $paymentMethod) {
            if ($paymentMethod->getCode() === $paymentMethodCode) {
                return $paymentMethod;
            }
        }

        throw new Exception('Payment method "' . $paymentMethodCode . '" not found');
    }
}
