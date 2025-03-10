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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\PaymentOptionsBuilder;

use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\IdealConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Giftcard\EdenredGiftcardConfigProvider;

class SettingsBuilder
{
    public const GATEWAYS = 'gateways';
    public const SHOW_PRE = 'show_pre';

    /**
     * @var GatewayConfig
     */
    private $gatewayConfig;

    /**
     * @var EdenredGiftcardConfigProvider
     */
    private $edenredGiftcardConfigProvider;

    /**
     * @param GatewayConfig $gatewayConfig
     * @param EdenredGiftcardConfigProvider $edenredGiftcardConfigProvider
     */
    public function __construct(
        GatewayConfig $gatewayConfig,
        EdenredGiftcardConfigProvider $edenredGiftcardConfigProvider
    ) {
        $this->gatewayConfig = $gatewayConfig;
        $this->edenredGiftcardConfigProvider = $edenredGiftcardConfigProvider;
    }

    /**
     * Build the settings that are required depending on the payment method
     *
     * @param Order $order
     * @param Payment $payment
     * @param OrderRequest $orderRequest
     * @return array
     */
    public function build(Order $order, Payment $payment, OrderRequest $orderRequest): array
    {
        if ($payment->getMethod() === IdealConfigProvider::CODE) {
            return $this->getSettingsForIdeal();
        }

        if ($payment->getMethod() === EdenredGiftcardConfigProvider::CODE) {
            return $this->getSettingsForEdenRed($order, $orderRequest);
        }

        return [];
    }

    /**
     * Build settings for the iDEAL payment method
     *
     * @return array
     */
    private function getSettingsForIdeal(): array
    {
        $this->gatewayConfig->setMethodCode(IdealConfigProvider::CODE);

        if ($this->gatewayConfig->getValue(Config::SHOW_PAYMENT_PAGE)) {
            return [
                self::GATEWAYS => [
                    $this->gatewayConfig->getValue('gateway_code') => [
                        self::SHOW_PRE => true
                    ]
                ]
            ];
        }

        return [];
    }

    /**
     * Build settings for the Edenred payment method
     *
     * @param Order $order
     * @param OrderRequest $orderRequest
     * @return array
     */
    private function getSettingsForEdenRed(
        Order $order,
        OrderRequest $orderRequest
    ): array {
        $coupons = $this->edenredGiftcardConfigProvider->getAvailableCouponsByOrder($order);
        $settings = [
            'gateways' => [
                'coupons' => [
                    'allow' => array_map('strtoupper', $coupons),
                    'disabled' => count($coupons) === 0,
                ],
            ],
        ];

        // We have to set coupon as a gateway code if we have only one available coupon code
        if (count($coupons) === 1) {
            $orderRequest->addGatewayCode(strtoupper(reset($coupons)));
        }

        return $settings;
    }
}
