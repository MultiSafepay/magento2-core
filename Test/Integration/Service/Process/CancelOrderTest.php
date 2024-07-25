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
use MultiSafepay\ConnectCore\Service\Process\CancelOrder;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class CancelOrderTest extends AbstractTestCase
{
    /**
     * @var CancelOrder
     */
    private $cancelOrder;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->cancelOrder = $this->getObjectManager()->create(CancelOrder::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     *
     * @return void
     * @throws LocalizedException
     * @throws Exception
     */
    public function testCancelOrder(): void
    {
        $order = $this->getOrder();
        $result = $this->cancelOrder->execute($order, $this->getTransactionData());

        self::assertSame([StatusOperationInterface::SUCCESS_PARAMETER => true], $result);

        // Refresh the order
        $order = $this->getOrder();

        self::assertSame(Order::STATE_CANCELED, $order->getState());
    }
}
