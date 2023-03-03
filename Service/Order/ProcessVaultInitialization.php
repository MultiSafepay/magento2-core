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

namespace MultiSafepay\ConnectCore\Service\Order;

use Exception;
use Magento\Sales\Api\Data\OrderPaymentInterface;
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
     * Execute the Magento Vault transaction process
     *
     * @param string $orderId
     * @param OrderPaymentInterface $payment
     * @param array $paymentDetails
     * @return bool
     * @throws Exception
     */
    public function execute(
        string $orderId,
        OrderPaymentInterface $payment,
        array $paymentDetails
    ): bool {
        $this->logger->logInfoForOrder(
            $orderId,
            __('MultiSafepay initialize Vault process has been started')->render(),
            Logger::DEBUG
        );

        //Check if Vault needs to be initialized
        try {
            $isVaultInitialized = $this->vault->initialize($payment, $paymentDetails);
        } catch (Exception $exception) {
            $isVaultInitialized = false;
            $this->logger->logExceptionForOrder($orderId, $exception, Logger::DEBUG);
        }

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
