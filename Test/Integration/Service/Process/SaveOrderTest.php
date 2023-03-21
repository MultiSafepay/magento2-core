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
use Magento\Sales\Api\OrderRepositoryInterface;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Service\Process\SaveOrder;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class SaveOrderTest extends AbstractTestCase
{
    /**
     * @var SaveOrder
     */
    private $saveOrder;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->saveOrder = $this->getObjectManager()->create(SaveOrder::class);
        $this->orderRepository = $this->getObjectManager()->create(OrderRepositoryInterface::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     *
     * @return void
     * @throws LocalizedException
     * @throws Exception
     */
    public function testSaveOrder(): void
    {
        $order = $this->getOrder();
        $testMessage = ['this is a test message'];

        // Set a value in the extension attributes, for example the payment additional information
        $order->getPayment()->setAdditionalInformation($testMessage);

        // Save the order
        $response = $this->saveOrder->execute($order, $this->getTransactionData());

        // Retrieve the saved order through the order repository
        $savedOrder = $this->orderRepository->get($order->getId());

        // Check if the additional information is there, if it is, then the order has been successfully saved earlier
        self::assertSame($testMessage, $savedOrder->getPayment()->getAdditionalInformation());
        self::assertSame([StatusOperationInterface::SUCCESS_PARAMETER => true], $response);
    }
}
