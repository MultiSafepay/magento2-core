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
use MultiSafepay\ConnectCore\Service\Process\LogTransactionStatus;
use MultiSafepay\ConnectCore\Service\Process\ProcessInterface;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class LogTransactionStatusTest extends AbstractTestCase
{
    /**
     * @var LogTransactionStatus
     */
    private $logTransactionStatus;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->logTransactionStatus = $this->getObjectManager()->create(LogTransactionStatus::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     *
     * @return void
     * @throws LocalizedException
     * @throws Exception
     */
    public function testLogTransactionStatus(): void
    {
        $order = $this->getOrder();
        $response = $this->logTransactionStatus->execute($order, $this->getTransactionData());

        self::assertSame(
            [StatusOperationInterface::SUCCESS_PARAMETER => true, ProcessInterface::SAVE_ORDER => false],
            $response
        );
    }
}
