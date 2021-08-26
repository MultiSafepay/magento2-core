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
use MultiSafepay\ConnectCore\Util\CaptureUtil;

class CaptureBuilder implements OrderRequestBuilderInterface
{
    /**
     * @var CaptureUtil
     */
    private $captureUtil;

    /**
     * CaptureBuilder constructor.
     *
     * @param CaptureUtil $captureUtil
     */
    public function __construct(CaptureUtil $captureUtil)
    {
        $this->captureUtil = $captureUtil;
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param OrderRequest $orderRequest
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function build(OrderInterface $order, OrderPaymentInterface $payment, OrderRequest $orderRequest): void
    {
        if ($this->captureUtil->isCaptureManualPayment($payment)) {
            $orderRequest->addData(
                ['capture' => 'manual']
            );
        }
    }
}
