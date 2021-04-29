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

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\GatewayInfoBuilder\GatewayInfoBuilderInterface;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\IdealConfigProvider;

class GatewayInfoBuilder implements OrderRequestBuilderInterface
{
    /**
     * @var GatewayInfoBuilderInterface[]
     */
    private $gatewayBuilders;

    /**
     * GatewayInfoBuilder constructor.
     *
     * @param GatewayInfoBuilderInterface[] $gatewayBuilders
     */
    public function __construct(
        array $gatewayBuilders
    ) {
        $this->gatewayBuilders = $gatewayBuilders;
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param OrderRequest $orderRequest
     * @throws LocalizedException
     */
    public function build(OrderInterface $order, OrderPaymentInterface $payment, OrderRequest $orderRequest): void
    {
        $paymentCode = $payment->getMethod();

        if (!isset($this->gatewayBuilders[$paymentCode])) {
            return;
        }

        // If transaction type is set to 'redirect' then do not add gateway info
        if ($payment->getMethodInstance()->getConfigData('transaction_type') === 'redirect') {
            return;
        }

        if ($paymentCode === IdealConfigProvider::CODE
            && !isset($payment->getAdditionalInformation()['issuer_id'])
        ) {
            return;
        }

        $orderRequest->addGatewayInfo(
            $this->gatewayBuilders[$paymentCode]->build($order, $payment)
        );
    }
}
