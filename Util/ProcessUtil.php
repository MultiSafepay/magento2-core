<?php

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Util;

use Magento\Sales\Api\Data\OrderInterface;
use MultiSafepay\ConnectCore\Service\Process\ProcessInterface;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class ProcessUtil
{
    /**
     * @param array $processes
     * @param OrderInterface $order
     * @param array $transaction
     * @return array
     */
    public function executeProcesses(array $processes, OrderInterface $order, array $transaction): array
    {
        /** @var ProcessInterface $process */
        foreach ($processes as $process) {
            $response = $process->execute($order, $transaction);

            if (!$response[StatusOperationInterface::SUCCESS_PARAMETER]) {
                return $response;
            }
        }

        return [StatusOperationInterface::SUCCESS_PARAMETER => true];
    }
}
