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

namespace MultiSafepay\ConnectCore\Service\Order;

use Exception;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\ConnectCore\Api\RecurringDetailsInterface;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\Vault;

class ProcessVaultInitialization
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Vault
     */
    private $vault;

    /**
     * ProcessVaultInitialization constructor.
     *
     * @param Logger $logger
     * @param Vault $vault
     */
    public function __construct(Logger $logger, Vault $vault)
    {
        $this->logger = $logger;
        $this->vault = $vault;
    }

    /**
     * @param string $orderId
     * @param OrderPaymentInterface $payment
     * @param array $paymentDetails
     * @param string $transactionType
     * @return bool
     * @throws Exception
     */
    public function execute(
        string $orderId,
        OrderPaymentInterface $payment,
        array $paymentDetails,
        string $transactionType
    ): bool {
        $this->logger->logInfoForOrder(
            $orderId,
            __('MultiSafepay initialize Vault process has been started')->render(),
            Logger::DEBUG
        );

        //Check if Vault needs to be initialized
        $isVaultInitialized = $this->vault->initialize($payment, [
            RecurringDetailsInterface::RECURRING_ID => $paymentDetails['recurring_id'] ?? '',
            RecurringDetailsInterface::TYPE => $transactionType,
            RecurringDetailsInterface::EXPIRATION_DATE => $paymentDetails['card_expiry_date'] ?? '',
            RecurringDetailsInterface::CARD_LAST4 => $paymentDetails['last4'] ?? '',
        ]);

        if ($isVaultInitialized) {
            $this->logger->logInfoForOrder($orderId, __('Vault has been initialized.')->render(), Logger::DEBUG);
        }

        $this->logger->logInfoForOrder(
            $orderId,
            __('MultiSafepay initialize Vault process has ended')->render(),
            Logger::DEBUG
        );

        return $isVaultInitialized;
    }
}
