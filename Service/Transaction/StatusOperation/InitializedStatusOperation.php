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

namespace MultiSafepay\ConnectCore\Service\Transaction\StatusOperation;

use Exception;
use Magento\Sales\Api\Data\OrderInterface;
use MultiSafepay\ConnectCore\Service\Process\AddPaymentLink;
use MultiSafepay\ConnectCore\Service\Process\LogTransactionStatus;
use MultiSafepay\ConnectCore\Service\Process\ProcessInterface;
use MultiSafepay\ConnectCore\Service\Process\SaveOrder;
use MultiSafepay\ConnectCore\Service\Process\SendOrderConfirmation;
use MultiSafepay\ConnectCore\Service\Process\UpdateOrderStatus;

class InitializedStatusOperation implements StatusOperationInterface
{
    /**
     * @var LogTransactionStatus
     */
    private $logTransactionStatus;

    /**
     * @var UpdateOrderStatus
     */
    private $updateOrderStatus;

    /**
     * @var SendOrderConfirmation
     */
    private $sendOrderConfirmation;

    /**
     * @var SaveOrder
     */
    private $saveOrder;

    /**
     * @var AddPaymentLink
     */
    private $addPaymentLink;

    /**
     * InitializedStatusOperation constructor
     *
     * @param LogTransactionStatus $logTransactionStatus
     * @param UpdateOrderStatus $updateOrderStatus
     * @param SendOrderConfirmation $sendOrderConfirmation
     * @param AddPaymentLink $addPaymentLink
     * @param SaveOrder $saveOrder
     */
    public function __construct(
        LogTransactionStatus $logTransactionStatus,
        UpdateOrderStatus $updateOrderStatus,
        SendOrderConfirmation $sendOrderConfirmation,
        AddPaymentLink $addPaymentLink,
        SaveOrder $saveOrder
    ) {
        $this->logTransactionStatus = $logTransactionStatus;
        $this->updateOrderStatus = $updateOrderStatus;
        $this->sendOrderConfirmation = $sendOrderConfirmation;
        $this->addPaymentLink = $addPaymentLink;
        $this->saveOrder = $saveOrder;
    }

    /**
     * Execute the initialized status operation
     *
     * @param OrderInterface $order
     * @param array $transaction
     * @return array
     * @throws Exception
     */
    public function execute(OrderInterface $order, array $transaction): array
    {
        $saveOrder = false;

        $processes = [
            $this->logTransactionStatus,
            $this->sendOrderConfirmation,
            $this->updateOrderStatus,
            $this->addPaymentLink
        ];

        /** @var ProcessInterface $process */
        foreach ($processes as $process) {
            $response = $process->execute($order, $transaction);

            if ($response[ProcessInterface::SAVE_ORDER]) {
                $saveOrder = true;
            }

            if (!$response[self::SUCCESS_PARAMETER]) {
                return $response;
            }
        }

        if ($saveOrder) {
            $this->saveOrder->execute($order, $transaction);
        }

        return [self::SUCCESS_PARAMETER => true];
    }
}
