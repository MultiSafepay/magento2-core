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
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\TransactionTypeBuilder;

class IdealDataAssignObserver extends AbstractDataAssignObserver
{

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        $data = $this->readDataArgument($observer);
        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        $payment = $this->readPaymentModelArgument($observer);

        if (empty($additionalData['issuer_id'])) {
            $payment->setAdditionalInformation(
                'transaction_type',
                TransactionTypeBuilder::TRANSACTION_TYPE_REDIRECT_VALUE
            );

            return;
        }

        $payment->setAdditionalInformation('transaction_type', TransactionTypeBuilder::TRANSACTION_TYPE_DIRECT_VALUE);
        $payment->setAdditionalInformation('issuer_id', $additionalData['issuer_id']);
    }
}
