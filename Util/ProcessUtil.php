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

namespace MultiSafepay\ConnectCore\Util;

use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Service\Process\ProcessInterface;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class ProcessUtil
{
    public const STOP_PROCESSING = 'stop_processing';

    /**
     * @param array $processes
     * @param Order $order
     * @param array $transaction
     * @return array
     */
    public function executeProcesses(array $processes, Order $order, array $transaction): array
    {
        /** @var ProcessInterface $process */
        foreach ($processes as $process) {
            $response = $process->execute($order, $transaction);
            $isSuccess = (bool)($response[StatusOperationInterface::SUCCESS_PARAMETER] ?? false);

            if (!$isSuccess) {
                return $response;
            }

            $shouldStop = (bool)($response[self::STOP_PROCESSING] ?? false);

            if ($shouldStop) {
                $response[StatusOperationInterface::SUCCESS_PARAMETER] = true;
                unset($response[self::STOP_PROCESSING]);

                return $response;
            }
        }

        return [StatusOperationInterface::SUCCESS_PARAMETER => true];
    }
}
