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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\GatewayInfoBuilder;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Meta;

class DirectBankTransferGatewayInfoBuilder implements GatewayInfoBuilderInterface
{
    /**
     * @var Meta
     */
    private $meta;

    /**
     * GatewayInfo constructor.
     *
     * @param Meta $meta
     */
    public function __construct(
        Meta $meta
    ) {
        $this->meta = $meta;
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @return Meta
     */
    public function build(OrderInterface $order, OrderPaymentInterface $payment): Meta
    {
        $additionalInformation = $payment->getAdditionalInformation();

        return $this->meta->addData([
            'account_id' => $additionalInformation['account_id'],
            'account_holder_name' => $additionalInformation['account_holder_name'],
            'account_holder_city' => $additionalInformation['account_holder_city'],
            'account_holder_country' => $additionalInformation['account_holder_country'],
            'account_holder_iban' => $additionalInformation['account_holder_iban'],
            'account_holder_bic' => $additionalInformation['account_holder_bic']
        ]);
    }
}
