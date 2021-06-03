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

use Magento\Payment\Gateway\Config\Config;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\Api\Transactions\OrderRequest;

class TransactionTypeBuilder implements OrderRequestBuilderInterface
{
    public const TRANSACTION_TYPE_DIRECT_VALUE = 'direct';
    public const TRANSACTION_TYPE_REDIRECT_VALUE = 'redirect';

    /**
     * @var Config
     */
    private $config;

    /**
     * TransactionTypeBuilder constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param OrderRequest $orderRequest
     * @return void
     */
    public function build(
        OrderInterface $order,
        OrderPaymentInterface $payment,
        OrderRequest $orderRequest
    ): void {
        $transactionType = (string)$this->config->getValue('transaction_type');
        if (!$transactionType) {
            $transactionType = $payment->getAdditionalInformation()['transaction_type']
                               ?? self::TRANSACTION_TYPE_REDIRECT_VALUE;
        }

        $orderRequest->addType($transactionType);
    }
}
