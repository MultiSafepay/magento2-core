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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\AdditionalDataBuilder;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class CreditCardAdditionalDataBuilder implements AdditionalDataBuilderInterface
{
    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @return array
     */
    public function build(OrderInterface $order, OrderPaymentInterface $payment): array
    {
        $additionalInformation = $payment->getAdditionalInformation();

        return isset($additionalInformation['payload']) && $additionalInformation['payload']
            ? [
                'payment_data' => [
                    'payload' => $payment->getAdditionalInformation()['payload'] ?? '',
                ],
            ]
            : [];
    }
}
