<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * Copyright © 2022 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Test\Integration\Service\Process;

use Magento\Framework\Exception\LocalizedException;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Service\Process\AddGiftCardInformation;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class AddGiftCardInformationTest extends AbstractTestCase
{
    /**
     * @var AddGiftCardInformation
     */
    private $addGiftCardInformation;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->addGiftCardInformation = $this->getObjectManager()->create(AddGiftCardInformation::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     *
     * @return void
     * @throws LocalizedException
     * @throws Exception
     */
    public function testAddGiftCardInformation(): void
    {
        $order = $this->getOrder();
        $result = $this->addGiftCardInformation->execute($order, $this->getTransactionData());

        self::assertIsArray($result);
        self::assertArrayHasKey(StatusOperationInterface::SUCCESS_PARAMETER, $result);
        self::assertSame([StatusOperationInterface::SUCCESS_PARAMETER => true], $result);

        $paymentAdditionalInformation = $order->getPayment()->getAdditionalInformation();

        /** @var array $couponData */
        $couponData = $paymentAdditionalInformation['multisafepay_coupon_data'][0];

        self::assertArrayHasKey('amount', $couponData);
        self::assertSame($couponData['amount'], 500);
        self::assertArrayHasKey('coupon_brand', $couponData);
        self::assertSame($couponData['coupon_brand'], 'VVVBON');
        self::assertArrayHasKey('currency', $couponData);
        self::assertSame($couponData['currency'], 'EUR');
        self::assertArrayHasKey('description', $couponData);
        self::assertSame($couponData['description'], 'Coupon Intersolve/111112');
        self::assertArrayHasKey('status', $couponData);
        self::assertSame($couponData['status'], 'completed');
        self::assertArrayHasKey('type', $couponData);
        self::assertSame($couponData['type'], 'COUPON');
    }
}
