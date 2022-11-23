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
 * Copyright © 2022 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Observer\Gateway;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\TransactionTypeBuilder;

class CreditCardDataAssignObserver extends AbstractDataAssignObserver
{
    public const CREDIT_CARD_BRAND_PARAM_NAME = 'card_brand';

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        $data = $this->readDataArgument($observer);
        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        $payment = $this->readPaymentModelArgument($observer);

        /**
         * Return early because the event is being triggered by a GraphQL request which contains a different structure,
         * and for that reason the request will be wrongly set as a redirect in next step.
         */
        if (isset($additionalData['additional_information']['payload'])) {
            return;
        }
        
        if (empty($additionalData['payload'])) {
            $payment->setAdditionalInformation(
                'transaction_type',
                TransactionTypeBuilder::TRANSACTION_TYPE_REDIRECT_VALUE
            );

            return;
        }

        if (!empty($additionalData[self::CREDIT_CARD_BRAND_PARAM_NAME])) {
            $payment->setAdditionalInformation(
                self::CREDIT_CARD_BRAND_PARAM_NAME,
                $additionalData[self::CREDIT_CARD_BRAND_PARAM_NAME]
            );
        }

        $payment->setAdditionalInformation('transaction_type', TransactionTypeBuilder::TRANSACTION_TYPE_DIRECT_VALUE);
        $payment->setAdditionalInformation('payload', $additionalData['payload']);
    }
}
