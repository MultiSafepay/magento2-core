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
use MultiSafepay\ConnectCore\Service\Process\ReopenOrder;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class ReopenOrderTest extends AbstractTestCase
{
    /**
     * @var ReopenOrder
     */
    private $reopenOrder;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->reopenOrder = $this->getObjectManager()->create(ReopenOrder::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     *
     * @return void
     * @throws LocalizedException
     * @throws Exception
     */
    public function testReopenOrderSkip(): void
    {
        $order = $this->getOrder();
        $response = $this->reopenOrder->execute($order, $this->getTransactionData());

        self::assertSame([StatusOperationInterface::SUCCESS_PARAMETER => true], $response);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     *
     * @return void
     * @throws LocalizedException
     * @throws Exception
     */
    public function testReopenOrder(): void
    {
        $order = $this->getOrder();
        $order->setState(Order::STATE_CANCELED);
        $response = $this->reopenOrder->execute($order, $this->getTransactionData());

        self::assertSame([StatusOperationInterface::SUCCESS_PARAMETER => true], $response);
        self::assertSame(Order::STATE_NEW, $order->getState());
    }
}
