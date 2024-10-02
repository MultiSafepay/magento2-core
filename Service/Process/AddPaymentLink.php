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

namespace MultiSafepay\ConnectCore\Service\Process;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Service\PaymentLink;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class AddPaymentLink implements ProcessInterface
{
    /**
     * @var PaymentLink
     */
    private $paymentLink;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * AddPaymentLink constructor
     *
     * @param PaymentLink $paymentLink
     * @param Logger $logger
     */
    public function __construct(
        PaymentLink $paymentLink,
        Logger $logger
    ) {
        $this->paymentLink = $paymentLink;
        $this->logger = $logger;
    }

    /**
     * Add the payment link to the order comments
     *
     * @param Order $order
     * @param array $transaction
     * @return array
     * @throws Exception
     */
    public function execute(Order $order, array $transaction): array
    {
        $paymentLink = $this->paymentLink->getPaymentLinkFromOrder($order);

        if ($paymentLink) {
            try {
                $this->paymentLink->addPaymentLinkToOrderComments($order, $paymentLink, true);
                return [StatusOperationInterface::SUCCESS_PARAMETER => true, self::SAVE_ORDER => true];
            } catch (LocalizedException $localizedException) {
                $message = 'Failed adding the payment link to the order comments';
                $this->logger->logInfoForNotification($order->getIncrementId(), $message, $transaction);

                return [StatusOperationInterface::SUCCESS_PARAMETER => true, self::SAVE_ORDER => false];
            }
        }

        return [StatusOperationInterface::SUCCESS_PARAMETER => true, self::SAVE_ORDER => false];
    }
}
