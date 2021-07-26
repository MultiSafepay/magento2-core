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

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Store\Model\StoreManagerInterface;
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
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * PaymentOptions constructor.
     *
     * @param PaymentOptions $paymentOptions
     * @param SecureToken $secureToken
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        PaymentOptions $paymentOptions,
        SecureToken $secureToken,
        StoreManagerInterface $storeManager
    ) {
        $this->secureToken = $secureToken;
        $this->paymentOptions = $paymentOptions;
        $this->storeManager = $storeManager;
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
        $params = [
            'secureToken' => $this->secureToken->generate((string)$order->getRealOrderId()),
        ];

        $notificationUrl = $this->getUrl(self::NOTIFICATION_URL, $storeId);
        $redirectUrl = $this->getUrl(self::REDIRECT_URL, $storeId, $params);
        $cancelUrl = $this->getUrl(self::CANCEL_URL, $storeId, $params);

        $orderRequest->addPaymentOptions(
            $this->paymentOptions->addNotificationUrl($notificationUrl)
                ->addRedirectUrl($redirectUrl)
                ->addCancelUrl($cancelUrl)
                ->addCloseWindow(false)
                ->addNotificationMethod()
        );
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
