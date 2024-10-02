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
use Magento\Payment\Gateway\Config\Config;

class SecondsActiveBuilder implements OrderRequestBuilderInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * SecondsActiveBuilder constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
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
        if (!$this->config->getValue('custom_payment_link_lifetime')) {
            return;
        }

        $unit = (int)$this->config->getValue('custom_payment_link_lifetime_unit');
        $value = (int)$this->config->getValue('custom_payment_link_lifetime_value');

        switch ($unit) {
            case 0:
                $orderRequest->addSecondsActive($value);
                break;
            case 1:
                $orderRequest->addSecondsActive($value * 60);
                break;
            case 2:
                $orderRequest->addSecondsActive($value * 3600);
                break;
            case 3:
                $orderRequest->addDaysActive($value);
                break;
        }
    }
}
