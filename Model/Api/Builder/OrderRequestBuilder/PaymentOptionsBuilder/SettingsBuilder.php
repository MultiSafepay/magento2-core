<?php

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\PaymentOptionsBuilder;

use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\ConnectCore\Model\Ui\Giftcard\EdenredGiftcardConfigProvider;

class SettingsBuilder
{
    /**
     * @var EdenredGiftcardConfigProvider
     */
    private $edenredGiftcardConfigProvider;

    /**
     * @param EdenredGiftcardConfigProvider $edenredGiftcardConfigProvider
     */
    public function __construct(
        EdenredGiftcardConfigProvider $edenredGiftcardConfigProvider
    ) {
        $this->edenredGiftcardConfigProvider = $edenredGiftcardConfigProvider;
    }

    /**
     * Build the required settings for the payment options
     *
     * @param Order $order
     * @param OrderPaymentInterface $payment
     * @param OrderRequest $orderRequest
     * @return array
     */
    public function build(Order $order, OrderPaymentInterface $payment, OrderRequest $orderRequest): array
    {
        $settings = [];

        if ($payment->getMethod() === EdenredGiftcardConfigProvider::CODE) {
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
            /**
             * @todo create and move it to separate gateway code builder
             */
            if (count($coupons) === 1) {
                $orderRequest->addGatewayCode(strtoupper(reset($coupons)));
            }
        }

        return $settings;
    }
}
