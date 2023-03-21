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
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Service\Process\SetOrderPaymentReviewStatus;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class SetOrderPaymentReviewStatusTest extends AbstractTestCase
{
    /**
     * @var SetOrderPaymentReviewStatus
     */
    private $SetOrderPaymentReviewStatus;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->SetOrderPaymentReviewStatus = $this->getObjectManager()->create(SetOrderPaymentReviewStatus::class);
    }

    /**
     * @magentoDataFixture   Magento/Customer/_files/customer.php
     * @magentoDataFixture   Magento/Catalog/_files/product_simple.php
     *
     * @return void
     * @throws LocalizedException
     * @throws Exception
     */
    public function testSetOrderPaymentReviewStatus(): void
    {
        $this->includeFixtureFile('order_pending_payment_state', true);
        $order = $this->getOrder();
        $response = $this->SetOrderPaymentReviewStatus->execute($order, $this->getTransactionData());

        self::assertSame(Order::STATE_PAYMENT_REVIEW, $order->getState());
        self::assertSame(Order::STATE_PAYMENT_REVIEW, $order->getStatus());

        self::assertSame([StatusOperationInterface::SUCCESS_PARAMETER => true], $response);
    }
}
