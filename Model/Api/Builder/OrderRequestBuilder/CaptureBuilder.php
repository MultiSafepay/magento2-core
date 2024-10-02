<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * See DISCLAIMER.md for disclaimer details.
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
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
     * @param Order $order
     * @param Payment $payment
     * @param OrderRequest $orderRequest
     * @throws LocalizedException
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function build(Order $order, Payment $payment, OrderRequest $orderRequest): void
    {
        if ($this->captureUtil->isManualCaptureEnabled($payment)) {
            $orderRequest->addData(
                ['capture' => CaptureUtil::CAPTURE_TRANSACTION_TYPE_MANUAL]
            );
        }
    }
}
