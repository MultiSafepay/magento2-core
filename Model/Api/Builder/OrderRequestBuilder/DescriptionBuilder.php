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

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\Description;
use MultiSafepay\ConnectCore\Config\Config;

class DescriptionBuilder implements OrderRequestBuilderInterface
{
    /**
     * @var Description
     */
    private $description;

    /**
     * @var Config
     */
    private $config;

    /**
     * DescriptionBuilder constructor.
     *
     * @param Config $config
     * @param Description $description
     */
    public function __construct(
        Config $config,
        Description $description
    ) {
        $this->description = $description;
        $this->config = $config;
    }

    /**
     * @param Order $order
     * @param Payment $payment
     * @param OrderRequest $orderRequest
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function build(Order $order, Payment $payment, OrderRequest $orderRequest): void
    {
        $orderId = (string) $order->getRealOrderId();
        $customDescription = (string)$this->config->getValue(Config::TRANSACTION_DESCRIPTION);

        if (empty($customDescription)) {
            $this->description->addDescription(__('Payment for order #%1', $orderId)->render());
        } else {
            $filteredDescription = str_replace('{{order.increment_id}}', $orderId, $customDescription);
            $this->description->addDescription($filteredDescription);
        }

        $orderRequest->addDescription($this->description);
    }
}
