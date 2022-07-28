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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\GatewayInfoBuilder;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Issuer;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;

class IssuerGatewayInfoBuilder implements GatewayInfoBuilderInterface
{
    /**
     * @var Issuer
     */
    private $issuer;

    /**
     * GatewayInfo constructor.
     *
     * @param Issuer $issuer
     */
    public function __construct(
        Issuer $issuer
    ) {
        $this->issuer = $issuer;
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @return GatewayInfoInterface
     */
    public function build(OrderInterface $order, OrderPaymentInterface $payment): GatewayInfoInterface
    {
        return $this->issuer->addIssuerId($payment->getAdditionalInformation()['issuer_id']);
    }
}
