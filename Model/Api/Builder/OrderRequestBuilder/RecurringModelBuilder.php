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
 * Copyright © 2020 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\ConnectCore\Util\VaultUtil;

class RecurringModelBuilder implements OrderRequestBuilderInterface
{
    public const RECURRING_MODEL_TYPE = 'cardOnFile';

    /**
     * @var VaultUtil
     */
    private $vaultUtil;

    /**
     * RecurringModelBuilder constructor.
     *
     * @param VaultUtil $vaultUtil
     */
    public function __construct(
        VaultUtil $vaultUtil
    ) {
        $this->vaultUtil = $vaultUtil;
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
        if ($this->vaultUtil->validateVaultTokenEnabler($payment->getAdditionalInformation())) {
            $orderRequest->addRecurringModel(self::RECURRING_MODEL_TYPE);
        }
    }
}
