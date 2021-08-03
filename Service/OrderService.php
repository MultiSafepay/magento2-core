<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * Copyright © 2021 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Service;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\Api\Transactions\Transaction as TransactionStatus;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\Order\ProcessChangePaymentMethod;
use MultiSafepay\ConnectCore\Service\Order\ProcessOrderByTransactionStatus;
use MultiSafepay\ConnectCore\Service\Order\ProcessVaultInitialization;
use MultiSafepay\ConnectCore\Util\GiftcardUtil;
use MultiSafepay\ConnectCore\Util\JsonHandler;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderService
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var EmailSender
     */
    private $emailSender;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SdkFactory
     */
    private $sdkFactory;

    /**
     * @var GiftcardUtil
     */
    private $giftcardUtil;

    /**
     * @var ProcessVaultInitialization
     */
    private $processVaultInitialization;

    /**
     * @var ProcessChangePaymentMethod
     */
    private $processChangePaymentMethod;

    /**
     * @var ProcessOrderByTransactionStatus
     */
    private $processOrderByTransactionStatus;

    /**
     * @var JsonHandler
     */
    private $jsonHandler;

    /**
     * OrderService constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param EmailSender $emailSender
     * @param Logger $logger
     * @param SdkFactory $sdkFactory
     * @param GiftcardUtil $giftcardUtil
     * @param ProcessVaultInitialization $processVaultInitialization
     * @param ProcessChangePaymentMethod $processChangePaymentMethod
     * @param ProcessOrderByTransactionStatus $processOrderByTransactionStatus
     * @param JsonHandler $jsonHandler
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        EmailSender $emailSender,
        Logger $logger,
        SdkFactory $sdkFactory,
        GiftcardUtil $giftcardUtil,
        ProcessVaultInitialization $processVaultInitialization,
        ProcessChangePaymentMethod $processChangePaymentMethod,
        ProcessOrderByTransactionStatus $processOrderByTransactionStatus,
        JsonHandler $jsonHandler
    ) {
        $this->orderRepository = $orderRepository;
        $this->emailSender = $emailSender;
        $this->logger = $logger;
        $this->sdkFactory = $sdkFactory;
        $this->giftcardUtil = $giftcardUtil;
        $this->processVaultInitialization = $processVaultInitialization;
        $this->processChangePaymentMethod = $processChangePaymentMethod;
        $this->processOrderByTransactionStatus = $processOrderByTransactionStatus;
        $this->jsonHandler = $jsonHandler;
    }

    /**
     * @param OrderInterface $order
     * @param array $transaction
     * @throws ClientExceptionInterface
     * @throws LocalizedException
     * @throws Exception
     */
    public function processOrderTransaction(OrderInterface $order, array $transaction = []): void
    {
        $orderId = $order->getIncrementId();
        $this->logger->logInfoForOrder(
            $orderId,
            __('Order ID has been retrieved from the order')->render(),
            Logger::DEBUG
        );

        $transactionManager = $this->sdkFactory->create((int)$order->getStoreId())->getTransactionManager();

        if (!$transaction) {
            $this->logger->logInfoForOrder(
                $orderId,
                __('Transaction data is empty. Trying to retrieve transaction.')->render(),
                Logger::DEBUG
            );
            $transactionResponse = $transactionManager->get($orderId);
            $transaction = $transactionResponse->getData();
            $this->logger->logInfoForOrder(
                $orderId,
                __('Transaction data retrieved through API call')->render(),
                Logger::DEBUG
            );
        }

        $transactionLog = $transaction;
        unset($transactionLog['payment_details'], $transactionLog['payment_methods']);

        $this->logger->logInfoForOrder(
            $orderId,
            __(
                'Transaction data was retrieved: %1',
                $this->jsonHandler->convertToPrettyJSON($transactionLog)
            )->render(),
            Logger::DEBUG
        );

        $transactionStatus = $transaction['status'] ?? '';

        if (in_array($transactionStatus, [
                TransactionStatus::COMPLETED,
                TransactionStatus::INITIALIZED,
                TransactionStatus::RESERVED,
                TransactionStatus::SHIPPED,
            ], true)
            && $this->emailSender->sendOrderConfirmationEmail($order)) {
            $this->logger->logInfoForOrder(
                $orderId,
                __('Order confirmation email after transaction has been sent')->render(),
                Logger::DEBUG
            );
        }

        /** @var Payment $payment */
        if (!($payment = $order->getPayment())) {
            throw new LocalizedException(__('Can\'t get the payment from the order.'));
        }

        $paymentDetails = $transaction['payment_details'] ?? [];
        $transactionType = $paymentDetails['type'] ?? '';
        $gatewayCode = (string)$payment->getMethodInstance()->getConfigData('gateway_code');

        $this->processVaultInitialization->execute($orderId, $payment, $paymentDetails, $transactionType);
        $this->processChangePaymentMethod->execute($order, $payment, $transactionType, $gatewayCode, $transaction);
        $this->processOrderByTransactionStatus->execute(
            $order,
            $payment,
            $transactionManager,
            $transaction,
            $transactionStatus,
            $gatewayCode
        );

        if ($giftcardData = $this->giftcardUtil->getGiftcardPaymentDataFromTransaction($transaction)) {
            $payment->setAdditionalInformation(
                GiftcardUtil::MULTISAFEPAY_GIFTCARD_PAYMENT_ADDITIONAL_DATA_PARAM_NAME,
                $giftcardData
            );
        }

        $this->orderRepository->save($order);
        $this->logger->logInfoForOrder(
            $orderId,
            __('Order has been saved.')->render(),
            Logger::DEBUG
        );
    }
}
