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

namespace MultiSafepay\ConnectCore\Test\Integration;

use Magento\Payment\Model\Method\Adapter as PaymentMethodAdapter;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Class PaymentMethodTest to test the behaviour of a payment method
 */
class PaymentMethodTest extends AbstractTestCase
{
    /**
     * Test to see if the payment method is registered
     */
    public function testPaymentMethod()
    {
        $paymentMethodAdapter = $this->getPaymentMethodAdapter();
        $this->assertSame('multisafepay', $paymentMethodAdapter->getCode());

        /** @var CartInterface $quote */
        $quote = $this->createMock(CartInterface::class);
        $quote->method('getStoreId')->willReturn(0);
        $this->assertFalse($paymentMethodAdapter->isAvailable($quote));

        $this->assertTrue($paymentMethodAdapter->canUseCheckout());
    }

    /**
     * @return PaymentMethodAdapter
     */
    private function getPaymentMethodAdapter(): PaymentMethodAdapter
    {
        return $this->getObjectManager()->get('MultiSafepayFacade');
    }
}
