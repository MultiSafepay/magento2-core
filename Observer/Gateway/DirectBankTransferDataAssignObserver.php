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

namespace MultiSafepay\ConnectCore\Observer\Gateway;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class DirectBankTransferDataAssignObserver extends AbstractDataAssignObserver
{

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        $payment = $this->readPaymentModelArgument($observer);

        if (isset($additionalData['account_id'])) {
            $payment->setAdditionalInformation('account_id', $additionalData['account_id']);
        }

        if (isset($additionalData['account_holder_name'])) {
            $payment->setAdditionalInformation('account_holder_name', $additionalData['account_holder_name']);
        }

        if (isset($additionalData['account_holder_city'])) {
            $payment->setAdditionalInformation('account_holder_city', $additionalData['account_holder_city']);
        }

        if (isset($additionalData['account_holder_country'])) {
            $payment->setAdditionalInformation('account_holder_country', $additionalData['account_holder_country']);
        }

        if (isset($additionalData['account_holder_iban'])) {
            $payment->setAdditionalInformation('account_holder_iban', $additionalData['account_holder_iban']);
        }

        if (isset($additionalData['account_holder_bic'])) {
            $payment->setAdditionalInformation('account_holder_bic', $additionalData['account_holder_bic']);
        }
    }
}
