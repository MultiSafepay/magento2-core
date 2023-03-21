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
use MultiSafepay\ConnectCore\Service\Process\ProcessInterface;
use MultiSafepay\ConnectCore\Service\Process\UpdateOrderStatus;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class UpdateOrderStatusTest extends AbstractTestCase
{
    /**
     * @var UpdateOrderStatus
     */
    private $updateOrderStatus;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->updateOrderStatus = $this->getObjectManager()->create(UpdateOrderStatus::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     *
     * @return void
     * @throws LocalizedException
     * @throws Exception
     */
    public function testUpdateOrderStatusSkip(): void
    {
        $order = $this->getOrder();
        $response = $this->updateOrderStatus->execute($order, $this->getTransactionData());

        self::assertSame(
            [StatusOperationInterface::SUCCESS_PARAMETER => true, ProcessInterface::SAVE_ORDER => false],
            $response
        );
        self::assertSame(Order::STATE_PROCESSING, $order->getStatus());
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/status/initialized_status pending
     *
     * @return void
     * @throws LocalizedException
     * @throws Exception
     */
    public function testUpdateOrderStatus(): void
    {
        $order = $this->getOrder();
        $response = $this->updateOrderStatus->execute($order, $this->getTransactionData());

        self::assertSame(
            [StatusOperationInterface::SUCCESS_PARAMETER => true, ProcessInterface::SAVE_ORDER => true],
            $response
        );
        self::assertSame('pending', $order->getStatus());
    }
}
