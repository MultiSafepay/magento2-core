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

use Exception;
use Magento\Framework\Exception\LocalizedException;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Service\Process\AddPaymentLink;
use MultiSafepay\ConnectCore\Service\Process\ProcessInterface;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class AddPaymentLinkTest extends AbstractTestCase
{
    public const TEST_PAYMENT_LINK = 'https://test.123';

    /**
     * @var AddPaymentLink
     */
    private $addPaymentLink;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->addPaymentLink = $this->getObjectManager()->create(AddPaymentLink::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     *
     * @return void
     * @throws LocalizedException
     * @throws Exception
     */
    public function testAddPaymentLinkWithoutAdding(): void
    {
        $order = $this->getOrder();
        $result = $this->addPaymentLink->execute($order, $this->getTransactionData());

        self::assertSame(
            [StatusOperationInterface::SUCCESS_PARAMETER => true, ProcessInterface::SAVE_ORDER => false],
            $result
        );
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     *
     * @return void
     * @throws LocalizedException
     * @throws Exception
     */
    public function testAddPaymentLink(): void
    {
        $order = $this->getOrder();
        $order->getPayment()->setAdditionalInformation(['payment_link' => self::TEST_PAYMENT_LINK]);

        $result = $this->addPaymentLink->execute($order, $this->getTransactionData());
        $additionalInformation = $order->getPayment()->getAdditionalInformation();

        self::assertSame(
            [StatusOperationInterface::SUCCESS_PARAMETER => true, ProcessInterface::SAVE_ORDER => true],
            $result
        );
        self::assertArrayHasKey('payment_link', $additionalInformation);
        self::assertSame(self::TEST_PAYMENT_LINK, $additionalInformation['payment_link']);
        self::assertArrayHasKey('has_multisafepay_paymentlink_comment', $additionalInformation);
        self::assertTrue((bool)$additionalInformation['has_multisafepay_paymentlink_comment']);
    }
}
