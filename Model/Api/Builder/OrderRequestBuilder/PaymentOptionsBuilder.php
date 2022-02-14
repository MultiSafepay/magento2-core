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
 * Copyright © 2022 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Store\Model\StoreManagerInterface;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PaymentOptions;
use MultiSafepay\ConnectCore\Model\SecureToken;
use MultiSafepay\ConnectCore\Model\Ui\Giftcard\EdenredGiftcardConfigProvider;

class PaymentOptionsBuilder implements OrderRequestBuilderInterface
{
    public const NOTIFICATION_URL = 'multisafepay/connect/notification';
    public const REDIRECT_URL = 'multisafepay/connect/success';
    public const CANCEL_URL = 'multisafepay/connect/cancel';

    /**
     * @var PaymentOptions
     */
    private $paymentOptions;

    /**
     * @var SecureToken
     */
    private $secureToken;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var EdenredGiftcardConfigProvider
     */
    private $edenredGiftcardConfigProvider;

    /**
     * PaymentOptionsBuilder constructor.
     *
     * @param PaymentOptions $paymentOptions
     * @param SecureToken $secureToken
     * @param StoreManagerInterface $storeManager
     * @param EdenredGiftcardConfigProvider $edenredGiftcardConfigProvider
     */
    public function __construct(
        PaymentOptions $paymentOptions,
        SecureToken $secureToken,
        StoreManagerInterface $storeManager,
        EdenredGiftcardConfigProvider $edenredGiftcardConfigProvider
    ) {
        $this->secureToken = $secureToken;
        $this->paymentOptions = $paymentOptions;
        $this->storeManager = $storeManager;
        $this->edenredGiftcardConfigProvider = $edenredGiftcardConfigProvider;
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param OrderRequest $orderRequest
     * @return void
     * @throws NoSuchEntityException
     */
    public function build(
        OrderInterface $order,
        OrderPaymentInterface $payment,
        OrderRequest $orderRequest
    ): void {
        $storeId = $order->getStoreId();
        $this->storeManager->setCurrentStore($order->getStoreId());
        $params = [
            'secureToken' => $this->secureToken->generate((string)$order->getRealOrderId()),
        ];

        $notificationUrl = $this->getUrl(self::NOTIFICATION_URL, $storeId);
        $redirectUrl = $this->getUrl(self::REDIRECT_URL, $storeId, $params);
        $cancelUrl = $this->getUrl(self::CANCEL_URL, $storeId, $params);
        $paymentOptions = $this->paymentOptions->addNotificationUrl($notificationUrl)
            ->addRedirectUrl($redirectUrl)
            ->addCancelUrl($cancelUrl)
            ->addCloseWindow(false)
            ->addNotificationMethod();

        if ($additionalSettings = $this->getAdditionalSettings($order, $payment, $orderRequest)) {
            $paymentOptions->addSettings($additionalSettings);
        }

        $orderRequest->addPaymentOptions($paymentOptions);
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param OrderRequest $orderRequest
     * @return array|\array[][]
     */
    private function getAdditionalSettings(
        OrderInterface $order,
        OrderPaymentInterface $payment,
        OrderRequest $orderRequest
    ): array {
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

    /**
     * @throws NoSuchEntityException
     */
    private function getUrl(string $endPoint, $storeId = null, array $params = null): string
    {
        return $this->storeManager->getStore($storeId)->getBaseUrl()
               . $endPoint
               . ($params ? "?" . http_build_query($params) : '');
    }
}
