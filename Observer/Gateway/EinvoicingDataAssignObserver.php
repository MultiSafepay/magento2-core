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

namespace MultiSafepay\ConnectCore\Observer\Gateway;

use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\TransactionTypeBuilder;

class EinvoicingDataAssignObserver extends AbstractDataAssignObserver
{

    /**
     * @inheritDoc
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        $payment = $this->readPaymentModelArgument($observer);
        $transactionType = $payment->getMethodInstance()->getConfigData('transaction_type');

        if ($transactionType === 'redirect') {
            return;
        }

        if (empty($additionalData)) {
            return;
        }

        if ($transactionType === 'payment_component' && isset($additionalData['payload'])) {
            $payment->setAdditionalInformation('payload', $additionalData['payload']);

            return;
        }

        if (isset($additionalData['date_of_birth'])) {
            $payment->setAdditionalInformation('date_of_birth', $additionalData['date_of_birth']);
        }

        if (isset($additionalData['account_number'])) {
            $payment->setAdditionalInformation('account_number', $additionalData['account_number'] ?? '');
        }

        if (isset($additionalData['email_address'])) {
            $payment->setAdditionalInformation('email_address', $additionalData['email_address'] ?? '');
        }

        $payment->setAdditionalInformation('transaction_type', TransactionTypeBuilder::TRANSACTION_TYPE_DIRECT_VALUE);
    }
}
