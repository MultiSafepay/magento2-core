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

namespace MultiSafepay\ConnectCore\Test\Integration\Payment;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\Method\Adapter as PaymentMethodAdapter;

class IdealDirectTest extends AbstractPaymentTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws LocalizedException
     */
    public function testPaymentHandling()
    {
        $payment = $this->getPayment('ideal', 'direct', '0031');
        $order = $this->getOrder();
        $this->runPaymentAction('IdealFacade', $payment, $order);
    }
}
