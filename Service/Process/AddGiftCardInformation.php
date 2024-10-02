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
use Magento\Payment\Model\Info;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\GiftcardUtil;
use MultiSafepay\ConnectCore\Service\Transaction\StatusOperation\StatusOperationInterface;

class AddGiftCardInformation implements ProcessInterface
{
    /**
     * @var GiftcardUtil
     */
    private $giftcardUtil;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param GiftcardUtil $giftcardUtil
     * @param Logger $logger
     */
    public function __construct(
        GiftcardUtil $giftcardUtil,
        Logger $logger
    ) {
        $this->giftcardUtil = $giftcardUtil;
        $this->logger = $logger;
    }

    /**
     * Add gift card information to the order
     *
     * @param Order $order
     * @param array $transaction
     * @return array
     * @throws Exception
     */
    public function execute(Order $order, array $transaction): array
    {
        /** @var Info $payment */
        $payment = $order->getPayment();

        if ($payment === null) {
            $this->logger->logInfoForNotification(
                $order->getIncrementId(),
                'Payment could not be found when trying to add gift card',
                $transaction
            );
            return [StatusOperationInterface::SUCCESS_PARAMETER => true];
        }

        $giftcardData = $this->giftcardUtil->getGiftcardPaymentDataFromTransaction($transaction);

        if ($giftcardData) {
            $payment->setAdditionalInformation(
                GiftcardUtil::MULTISAFEPAY_GIFTCARD_PAYMENT_ADDITIONAL_DATA_PARAM_NAME,
                $giftcardData
            );

            $this->logger->logInfoForNotification(
                $order->getIncrementId(),
                'Gift card information added to the payment additional information',
                $transaction
            );
        }

        return [StatusOperationInterface::SUCCESS_PARAMETER => true];
    }
}
