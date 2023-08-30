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

use MultiSafepay\Api\Transactions\Transaction;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Service\Process\DelayExecution;

class DelayExecutionTest extends AbstractTestCase
{
    /**
     * @var DelayExecution
     */
    private $delayExecution;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->delayExecution = $this->getObjectManager()->create(DelayExecution::class);
    }

    /**
     * @return void
     */
    public function testDelayExecution(): void
    {
        $startTime = date('s', time() + DelayExecution::TIMEOUT);
        $this->delayExecution->execute(Transaction::COMPLETED);
        $endTime = date('s');

        self::assertThat(
            $endTime,
            self::logicalAnd(
                $this->greaterThanOrEqual($startTime - 0.5),
                $this->lessThanOrEqual($startTime + 0.5)
            )
        );
    }
}
