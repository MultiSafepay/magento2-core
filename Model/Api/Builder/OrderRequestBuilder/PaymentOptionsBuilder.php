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

use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PaymentOptions;
use MultiSafepay\ConnectCore\Model\SecureToken;

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
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * PaymentOptions constructor.
     *
     * @param PaymentOptions $paymentOptions
     * @param SecureToken $secureToken
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        PaymentOptions $paymentOptions,
        SecureToken $secureToken,
        UrlInterface $urlBuilder
    ) {
        $this->secureToken = $secureToken;
        $this->paymentOptions = $paymentOptions;
        $this->urlBuilder = $urlBuilder;
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
        $orderId = (string) $order->getRealOrderId();
        $secureToken = $this->secureToken->generate($orderId);

        $parameters = [
            '_nosid' => true,
            '_query' => [
                'secureToken' => $secureToken
            ]
        ];

        $notificationUrl = $this->urlBuilder->getDirectUrl(self::NOTIFICATION_URL);
        $redirectUrl = $this->urlBuilder->getDirectUrl(self::REDIRECT_URL, $parameters);
        $cancelUrl = $this->urlBuilder->getDirectUrl(self::CANCEL_URL, $parameters);

        $orderRequest->addPaymentOptions(
            $this->paymentOptions->addNotificationUrl($notificationUrl)
                ->addRedirectUrl($redirectUrl)
                ->addCancelUrl($cancelUrl)
                ->addCloseWindow(false)
                ->addNotificationMethod('GET')
        );
    }
}
