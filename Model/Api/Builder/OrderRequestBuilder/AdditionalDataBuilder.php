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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\AdditionalDataBuilder\AdditionalDataBuilderInterface;

class AdditionalDataBuilder implements OrderRequestBuilderInterface
{
    /**
     * @var AdditionalDataBuilderInterface[]
     */
    private $additionalDataBuilders;

    /**
     * AdditionalDataBuilder constructor.
     *
     * @param AdditionalDataBuilderInterface[] $additionalDataBuilders
     */
    public function __construct(
        array $additionalDataBuilders
    ) {
        $this->additionalDataBuilders = $additionalDataBuilders;
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param OrderRequest $orderRequest
     */
    public function build(OrderInterface $order, OrderPaymentInterface $payment, OrderRequest $orderRequest): void
    {
        $paymentCode = $payment->getMethod();

        if (!isset($this->additionalDataBuilders[$paymentCode])) {
            return;
        }

        $additionalData = [];

        foreach ($this->additionalDataBuilders[$paymentCode] as $additionalDataBuilder) {
            $additionalData[] = $additionalDataBuilder->build($order, $payment);
        }

        $orderRequest->addData(array_merge([], ...$additionalData));
    }
}
