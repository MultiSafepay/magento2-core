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
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\TransactionTypeBuilder;
use MultiSafepay\ConnectCore\Util\JsonHandler;

class WalletDataAssignObserver extends AbstractDataAssignObserver
{
    /**
     * @var JsonHandler
     */
    private $jsonHandler;

    /**
     * @param JsonHandler $jsonHandler
     */
    public function __construct(
        JsonHandler $jsonHandler
    ) {
        $this->jsonHandler = $jsonHandler;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        $data = $this->readDataArgument($observer);
        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        $payment = $this->readPaymentModelArgument($observer);

        if (!empty($additionalData['payload'])) {
            $additionalData = $this->jsonHandler->readJSON($additionalData['payload']);

            $payment->setAdditionalInformation(
                'transaction_type',
                TransactionTypeBuilder::TRANSACTION_TYPE_DIRECT_VALUE
            );
            $payment->setAdditionalInformation('payment_token', $additionalData['token'] ?? '');
            $payment->setAdditionalInformation('browser_info', $additionalData['browser_info'] ?? '');
        }
    }
}
