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
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\AmexConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\CreditCardConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\MastercardConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\VisaConfigProvider;

class RecurringIdBuilder implements OrderRequestBuilderInterface
{
    public const ALLOWED_METHODS = [
        AmexConfigProvider::VAULT_CODE,
        CreditCardConfigProvider::VAULT_CODE,
        MastercardConfigProvider::VAULT_CODE,
        VisaConfigProvider::VAULT_CODE,
    ];

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param OrderRequest $orderRequest
     * @throws LocalizedException
     */
    public function build(OrderInterface $order, OrderPaymentInterface $payment, OrderRequest $orderRequest): void
    {
        if (in_array($payment->getMethod(), self::ALLOWED_METHODS, true)) {
            $extensionAttributes = $payment->getExtensionAttributes();
            if (!$extensionAttributes || !$extensionAttributes->getVaultPaymentToken()) {
                $this->logger->logMissingPaymentToken($order->getIncrementId());
                throw new LocalizedException(
                    __('This payment method is not available at the moment. Please try another payment method.')
                );
            }
            $orderRequest->addRecurringId($extensionAttributes->getVaultPaymentToken()->getGatewayToken());
            $orderRequest->addRecurringModel(RecurringModelBuilder::RECURRING_MODEL_TYPE);
        }
    }
}
