<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * See DISCLAIMER.md for disclaimer details.
 */
declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Test\Integration\Util;

use Magento\Framework\Exception\LocalizedException;
use MultiSafepay\ConnectCore\Service\Process\ProcessInterface;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\ProcessUtil;
use PHPUnit\Framework\MockObject\Exception;

class ProcessUtilTest extends AbstractTestCase
{
    private const DUMMY_TRANSACTION_DATA = ['transaction_id' => 'psp_123'];

    /**
     * Tests that if a process returns a failure response,
     * the ProcessUtil stops executing further processes and returns that response.
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @throws LocalizedException
     * @throws Exception
     */
    public function testStopsOnFailureAndDoesNotExecuteNextProcess(): void
    {
        $util = new ProcessUtil();
        $order = $this->getOrder();

        $process1 = $this->createMock(ProcessInterface::class);
        $process1->expects(self::once())
            ->method('execute')
            ->with($order, self::DUMMY_TRANSACTION_DATA)
            ->willReturn([
                StatusOperationInterface::SUCCESS_PARAMETER => false,
                'message' => 'nope',
            ]);

        $process2 = $this->createMock(ProcessInterface::class);
        $process2->expects(self::never())
            ->method('execute');

        $result = $util->executeProcesses([$process1, $process2], $order, self::DUMMY_TRANSACTION_DATA);

        self::assertFalse($result[StatusOperationInterface::SUCCESS_PARAMETER]);
        self::assertSame('nope', $result['message']);
    }

    /**
     * Tests that if a process sets the stop processing flag, the ProcessUtil stops executing further processes.
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @throws LocalizedException
     * @throws Exception
     */
    public function testStopsWhenStopProcessingFlagIsSetAndUnsetsInternalFlag(): void
    {
        $order = $this->getOrder();
        $util = new ProcessUtil();

        $process1 = $this->createMock(ProcessInterface::class);
        $process1->expects(self::once())
            ->method('execute')
            ->with($order, self::DUMMY_TRANSACTION_DATA)
            ->willReturn([
                StatusOperationInterface::SUCCESS_PARAMETER => true,
                ProcessUtil::STOP_PROCESSING => true,
                ProcessInterface::SAVE_ORDER => false,
                'message' => 'duplicate webhook',
            ]);

        $process2 = $this->createMock(ProcessInterface::class);
        $process2->expects(self::never())
            ->method('execute');

        $result = $util->executeProcesses([$process1, $process2], $order, self::DUMMY_TRANSACTION_DATA);

        self::assertTrue($result[StatusOperationInterface::SUCCESS_PARAMETER]);
        self::assertSame(false, $result[ProcessInterface::SAVE_ORDER]);
        self::assertSame('duplicate webhook', $result['message']);
        self::assertArrayNotHasKey(ProcessUtil::STOP_PROCESSING, $result);
    }

    /**
     * Tests that if all processes succeed, the ProcessUtil executes all of them and returns a success response.
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @throws LocalizedException
     * @throws Exception
     */
    public function testRunsAllProcessesWhenAllSucceed(): void
    {
        $util = new ProcessUtil();
        $order = $this->getOrder();

        $process1 = $this->createMock(ProcessInterface::class);
        $process1->expects(self::once())
            ->method('execute')
            ->with($order, self::DUMMY_TRANSACTION_DATA)
            ->willReturn([StatusOperationInterface::SUCCESS_PARAMETER => true]);

        $process2 = $this->createMock(ProcessInterface::class);
        $process2->expects(self::once())
            ->method('execute')
            ->with($order, self::DUMMY_TRANSACTION_DATA)
            ->willReturn([StatusOperationInterface::SUCCESS_PARAMETER => true]);

        $result = $util->executeProcesses([$process1, $process2], $order, self::DUMMY_TRANSACTION_DATA);

        self::assertSame([StatusOperationInterface::SUCCESS_PARAMETER => true], $result);
    }
}
