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

use Magento\Sales\Api\Data\OrderInterface;
use MultiSafepay\ConnectCore\Service\Process\AddCardPaymentInformation;
use MultiSafepay\ConnectCore\Service\Process\AddGiftCardInformation;
use MultiSafepay\ConnectCore\Service\Process\AddInvoiceToTransaction;
use MultiSafepay\ConnectCore\Service\Process\AddPaymentLink;
use MultiSafepay\ConnectCore\Service\Process\ChangePaymentMethod;
use MultiSafepay\ConnectCore\Service\Process\CreateInvoice;
use MultiSafepay\ConnectCore\Service\Process\InitializeVault;
use MultiSafepay\ConnectCore\Service\Process\LogTransactionStatus;
use MultiSafepay\ConnectCore\Service\Process\ProcessInterface;
use MultiSafepay\ConnectCore\Service\Process\ReopenOrder;
use MultiSafepay\ConnectCore\Service\Process\SaveOrder;
use MultiSafepay\ConnectCore\Service\Process\SendInvoice;
use MultiSafepay\ConnectCore\Service\Process\SendOrderConfirmation;
use MultiSafepay\ConnectCore\Service\Process\SetOrderProcessingState;
use MultiSafepay\ConnectCore\Service\Process\SetOrderProcessingStatus;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CompletedStatusOperation implements StatusOperationInterface
{
    /**
     * @var LogTransactionStatus
     */
    private $logTransactionStatus;

    /**
     * @var ChangePaymentMethod
     */
    private $changePaymentMethod;

    /**
     * @var ReopenOrder
     */
    private $reopenOrder;

    /**
     * @var SendOrderConfirmation
     */
    private $sendOrderConfirmation;

    /**
     * @var InitializeVault
     */
    private $initializeVault;

    /**
     * @var AddGiftCardInformation
     */
    private $addGiftcardInformation;

    /**
     * @var SaveOrder
     */
    private $saveOrder;

    /**
     * @var CreateInvoice
     */
    private $createInvoice;

    /**
     * @var AddPaymentLink
     */
    private $addPaymentLink;

    /**
     * @var SetOrderProcessingStatus
     */
    private $setOrderProcessingStatus;

    /**
     * @var SendInvoice
     */
    private $sendInvoice;

    /**
     * @var AddInvoiceToTransaction
     */
    private $addInvoiceToTransaction;

    /**
     * @var SetOrderProcessingState
     */
    private $setOrderProcessingState;

    /**
     * @var AddCardPaymentInformation
     */
    private $addCardPaymentInformation;

    /**
     * CompletedStatusOperation constructor
     *
     * @param LogTransactionStatus $logTransactionStatus
     * @param ChangePaymentMethod $changePaymentMethod
     * @param AddPaymentLink $addPaymentLink
     * @param ReopenOrder $reopenOrder
     * @param SendOrderConfirmation $sendOrderConfirmation
     * @param InitializeVault $initializeVault
     * @param CreateInvoice $createInvoice
     * @param AddGiftCardInformation $addGiftcardInformation
     * @param AddCardPaymentInformation $addCardPaymentInformation
     * @param SetOrderProcessingState $setOrderProcessingState
     * @param SetOrderProcessingStatus $setOrderProcessingStatus
     * @param SaveOrder $saveOrder
     * @param SendInvoice $sendInvoice
     * @param AddInvoiceToTransaction $addInvoiceToTransaction
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        LogTransactionStatus $logTransactionStatus,
        ChangePaymentMethod $changePaymentMethod,
        AddPaymentLink $addPaymentLink,
        ReopenOrder $reopenOrder,
        SendOrderConfirmation $sendOrderConfirmation,
        InitializeVault $initializeVault,
        CreateInvoice $createInvoice,
        AddGiftCardInformation $addGiftcardInformation,
        AddCardPaymentInformation $addCardPaymentInformation,
        SetOrderProcessingState $setOrderProcessingState,
        SetOrderProcessingStatus $setOrderProcessingStatus,
        SaveOrder $saveOrder,
        SendInvoice $sendInvoice,
        AddInvoiceToTransaction $addInvoiceToTransaction
    ) {
        $this->logTransactionStatus = $logTransactionStatus;
        $this->changePaymentMethod = $changePaymentMethod;
        $this->addPaymentLink = $addPaymentLink;
        $this->reopenOrder = $reopenOrder;
        $this->sendOrderConfirmation = $sendOrderConfirmation;
        $this->initializeVault = $initializeVault;
        $this->createInvoice = $createInvoice;
        $this->addGiftcardInformation = $addGiftcardInformation;
        $this->addCardPaymentInformation = $addCardPaymentInformation;
        $this->setOrderProcessingState = $setOrderProcessingState;
        $this->setOrderProcessingStatus = $setOrderProcessingStatus;
        $this->saveOrder = $saveOrder;
        $this->sendInvoice = $sendInvoice;
        $this->addInvoiceToTransaction = $addInvoiceToTransaction;
    }

    /**
     * Execute the completed status operation
     *
     * @param OrderInterface $order
     * @param array $transaction
     * @return array
     */
    public function execute(OrderInterface $order, array $transaction): array
    {
        $processes = [
            $this->logTransactionStatus,
            $this->sendOrderConfirmation,
            $this->reopenOrder,
            $this->initializeVault,
            $this->changePaymentMethod,
            $this->addPaymentLink,
            $this->setOrderProcessingState,
            $this->createInvoice,
            $this->addGiftcardInformation,
            $this->addCardPaymentInformation,
            $this->setOrderProcessingStatus,
            $this->saveOrder,
            $this->sendInvoice,
            $this->addInvoiceToTransaction
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
