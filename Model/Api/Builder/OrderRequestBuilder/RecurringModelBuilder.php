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

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\ConnectCore\Util\RecurringDataUtil;
use MultiSafepay\Exception\InvalidArgumentException;

class RecurringModelBuilder implements OrderRequestBuilderInterface
{
    public const RECURRING_MODEL_TYPE = 'cardOnFile';

    /**
     * @var RecurringDataUtil
     */
    private $recurringDataUtil;

    /**
     * RecurringModelBuilder constructor.
     *
     * @param RecurringDataUtil $recurringDataUtil
     */
    public function __construct(
        RecurringDataUtil $recurringDataUtil
    ) {
        $this->recurringDataUtil = $recurringDataUtil;
    }

    /**
     * @param Order $order
     * @param Payment $payment
     * @param OrderRequest $orderRequest
     * @return void
     *
     * @throws InvalidArgumentException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function build(Order $order, Payment $payment, OrderRequest $orderRequest): void
    {
        if ($this->recurringDataUtil->shouldAddRecurringData($payment->getAdditionalInformation())) {
            $orderRequest->addRecurringModel(self::RECURRING_MODEL_TYPE);
        }
    }
}
