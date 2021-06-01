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

namespace MultiSafepay\Test\Integration\Util;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\OfflinePayments\Model\Checkmo;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\IdealConfigProvider;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\PaymentMethodUtil;

class PaymentMethodUtilTest extends AbstractTestCase
{
    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentMethodUtil = $this->getObjectManager()->create(PaymentMethodUtil::class);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     *
     * @throws LocalizedException
     */
    public function testIsMultisafepayOrder(): void
    {
        $order = $this->getOrder();
        $order->getPayment()->setMethod(IdealConfigProvider::CODE);

        self::assertTrue($this->paymentMethodUtil->isMultisafepayOrder($order));
    }

    /**
     * @throws Exception
     */
    public function testIsMultisafepayCart(): void
    {
        $quote = $this->getQuote('tableRate');
        $quote->getPayment()->setMethod(IdealConfigProvider::CODE);

        self::assertTrue($this->paymentMethodUtil->isMultisafepayCart($quote));
    }

    public function testIsMultisafepayPaymentByCode(): void
    {
        self::assertTrue($this->paymentMethodUtil->isMultisafepayPaymentByCode(IdealConfigProvider::CODE));
        self::assertFalse(
            $this->paymentMethodUtil->isMultisafepayPaymentByCode(Checkmo::PAYMENT_METHOD_CHECKMO_CODE)
        );
    }
}
