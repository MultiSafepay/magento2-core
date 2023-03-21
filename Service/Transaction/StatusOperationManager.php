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

namespace MultiSafepay\ConnectCore\Service\Transaction;

use Exception;
use Magento\Sales\Api\Data\OrderInterface;
use MultiSafepay\Api\Transactions\Transaction;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\CanceledStatusOperation;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\GeneralStatusOperation;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\InitializedStatusOperation;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\UnclearedStatusOperation;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\CompletedStatusOperation;

class StatusOperationManager
{
    /**
     * @var CanceledStatusOperation
     */
    private $canceledStatusOperation;

    /**
     * @var CompletedStatusOperation
     */
    private $completedStatusOperation;

    /**
     * @var UnclearedStatusOperation
     */
    private $unclearedStatusOperation;

    /**
     * @var InitializedStatusOperation
     */
    private $initializedStatusOperation;

    /**
     * @var GeneralStatusOperation
     */
    private $generalStatusOperation;

    /**
     * StatusOperationManager constructor.
     *
     * @param CanceledStatusOperation $canceledStatusOperation
     * @param CompletedStatusOperation $completedStatusOperation
     * @param UnclearedStatusOperation $unclearedStatusOperation
     * @param InitializedStatusOperation $initializedStatusOperation
     * @param GeneralStatusOperation $generalStatusOperation
     */
    public function __construct(
        CanceledStatusOperation $canceledStatusOperation,
        CompletedStatusOperation $completedStatusOperation,
        UnclearedStatusOperation $unclearedStatusOperation,
        InitializedStatusOperation $initializedStatusOperation,
        GeneralStatusOperation $generalStatusOperation
    ) {
        $this->canceledStatusOperation = $canceledStatusOperation;
        $this->completedStatusOperation = $completedStatusOperation;
        $this->unclearedStatusOperation = $unclearedStatusOperation;
        $this->initializedStatusOperation = $initializedStatusOperation;
        $this->generalStatusOperation = $generalStatusOperation;
    }

    /**
     * Process status based on its own operation, response will always be ok if status does not have to be processed
     *
     * @param OrderInterface $order
     * @param array $transaction
     * @return array
     * @throws Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function processStatusOperation(OrderInterface $order, array $transaction): array
    {
        $response = 'ok';

        switch ($transaction['status']) {
            case Transaction::COMPLETED:
                $response = $this->completedStatusOperation->execute($order, $transaction);
                break;
            case Transaction::INITIALIZED:
                $response = $this->initializedStatusOperation->execute($order, $transaction);
                break;
            case Transaction::UNCLEARED:
                $response = $this->unclearedStatusOperation->execute($order, $transaction);
                break;
            case Transaction::EXPIRED:
            case Transaction::DECLINED:
            case Transaction::CANCELLED:
            case Transaction::VOID:
                $response = $this->canceledStatusOperation->execute($order, $transaction);
                break;
            case Transaction::RESERVED:
            case Transaction::CHARGEDBACK:
            case Transaction::REFUNDED:
            case Transaction::PARTIAL_REFUNDED:
            case Transaction::SHIPPED:
                $response = $this->generalStatusOperation->execute($order, $transaction);
                break;
        }

        return $response;
    }
}
