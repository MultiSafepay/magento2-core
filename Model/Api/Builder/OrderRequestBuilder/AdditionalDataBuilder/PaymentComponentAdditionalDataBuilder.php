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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\AdditionalDataBuilder;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class PaymentComponentAdditionalDataBuilder implements AdditionalDataBuilderInterface
{
    /**
     * Build the additional data that is required for the payment component transaction request
     *
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function build(OrderInterface $order, OrderPaymentInterface $payment): array
    {
        $additionalInformation = $payment->getAdditionalInformation() ?? [];

        if (!isset($additionalInformation['payload']) || !$additionalInformation['payload']) {
            return [];
        }

        $paymentData = ['payload' => $additionalInformation['payload']];

        if (isset($additionalInformation['tokenize']) && $additionalInformation['tokenize']) {
            $paymentData['tokenize'] = true;
        }

        return ['payment_data' => $paymentData];
    }
}
