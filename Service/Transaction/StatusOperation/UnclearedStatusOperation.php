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
use MultiSafepay\ConnectCore\Service\Process\AddUnclearedMessage;
use MultiSafepay\ConnectCore\Service\Process\ChangePaymentMethod;
use MultiSafepay\ConnectCore\Service\Process\LogTransactionStatus;
use MultiSafepay\ConnectCore\Service\Process\ProcessInterface;
use MultiSafepay\ConnectCore\Service\Process\ReopenOrder;
use MultiSafepay\ConnectCore\Service\Process\SaveOrder;
use MultiSafepay\ConnectCore\Service\Process\SendOrderConfirmation;
use MultiSafepay\ConnectCore\Service\Process\SetOrderPaymentReviewStatus;
use MultiSafepay\ConnectCore\Service\Process\UpdateOrderStatus;

class UnclearedStatusOperation implements StatusOperationInterface
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
     * @var ChangePaymentMethod
     */
    private $changePaymentMethod;

    /**
     * @var AddUnclearedMessage
     */
    private $addUnclearedMessage;

    /**
     * @var SaveOrder
     */
    private $saveOrder;

    /**
     * @var SendOrderConfirmation
     */
    private $sendOrderConfirmation;

    /**
     * @var SetOrderPaymentReviewStatus
     */
    private $setOrderPaymentReviewStatus;

    /**
     * @var ReopenOrder
     */
    private $reopenOrder;

    /**
     * UnclearedStatusOperation constructor
     *
     * @param LogTransactionStatus $logTransactionStatus
     * @param SendOrderConfirmation $sendOrderConfirmation
     * @param UpdateOrderStatus $updateOrderStatus
     * @param ChangePaymentMethod $changePaymentMethod
     * @param AddUnclearedMessage $addUnclearedMessage
     * @param SetOrderPaymentReviewStatus $setOrderPaymentReviewStatus
     * @param SaveOrder $saveOrder
     * @param ReopenOrder $reopenOrder
     */
    public function __construct(
        LogTransactionStatus $logTransactionStatus,
        SendOrderConfirmation $sendOrderConfirmation,
        UpdateOrderStatus $updateOrderStatus,
        ChangePaymentMethod $changePaymentMethod,
        AddUnclearedMessage $addUnclearedMessage,
        SetOrderPaymentReviewStatus $setOrderPaymentReviewStatus,
        SaveOrder $saveOrder,
        ReopenOrder $reopenOrder
    ) {
        $this->logTransactionStatus = $logTransactionStatus;
        $this->sendOrderConfirmation = $sendOrderConfirmation;
        $this->updateOrderStatus = $updateOrderStatus;
        $this->changePaymentMethod = $changePaymentMethod;
        $this->addUnclearedMessage = $addUnclearedMessage;
        $this->setOrderPaymentReviewStatus = $setOrderPaymentReviewStatus;
        $this->saveOrder = $saveOrder;
        $this->reopenOrder = $reopenOrder;
    }

    /**
     * Execute the uncleared status operation
     *
     * @param OrderInterface $order
     * @param array $transaction
     * @return array
     * @throws Exception
     */
    public function execute(OrderInterface $order, array $transaction): array
    {
        $processes = [
            $this->logTransactionStatus,
            $this->sendOrderConfirmation,
            $this->reopenOrder,
            $this->updateOrderStatus,
            $this->changePaymentMethod,
            $this->addUnclearedMessage,
            $this->setOrderPaymentReviewStatus,
            $this->saveOrder
        ];

        /** @var ProcessInterface $process */
        foreach ($processes as $process) {
            $response = $process->execute($order, $transaction);

            if (!$response[self::SUCCESS_PARAMETER]) {
                return $response;
            }
        }

        return [self::SUCCESS_PARAMETER => true];
    }
}
