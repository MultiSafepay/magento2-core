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

use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\StoreManagerInterface;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PaymentOptions;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\PaymentOptionsBuilder\SettingsBuilder;
use MultiSafepay\ConnectCore\Model\SecureToken;
use MultiSafepay\Exception\InvalidArgumentException;

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
     * @var SettingsBuilder
     */
    private $settingsBuilder;

    /**
     * PaymentOptionsBuilder constructor.
     *
     * @param PaymentOptions $paymentOptions
     * @param SecureToken $secureToken
     * @param StoreManagerInterface $storeManager
     * @param SettingsBuilder $settingsBuilder
     */
    public function __construct(
        PaymentOptions $paymentOptions,
        SecureToken $secureToken,
        StoreManagerInterface $storeManager,
        SettingsBuilder $settingsBuilder
    ) {
        $this->secureToken = $secureToken;
        $this->paymentOptions = $paymentOptions;
        $this->storeManager = $storeManager;
        $this->settingsBuilder = $settingsBuilder;
    }

    /**
     * @param Order $order
     * @param Payment $payment
     * @param OrderRequest $orderRequest
     * @return void
     * @throws NoSuchEntityException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function build(
        Order $order,
        Payment $payment,
        OrderRequest $orderRequest
    ): void {
        $storeId = (int)$order->getStoreId();
        $this->storeManager->setCurrentStore($order->getStoreId());
        $params = [
            'secureToken' => $this->secureToken->generate((string)$order->getRealOrderId()),
        ];

        $notificationUrl = $this->getUrl(self::NOTIFICATION_URL, $storeId, ['store_id' => $storeId]);
        $redirectUrl = $this->getUrl(self::REDIRECT_URL, $storeId, $params);
        $cancelUrl = $this->getUrl(self::CANCEL_URL, $storeId, $params);
        $paymentOptions = $this->paymentOptions->addNotificationUrl($notificationUrl)
            ->addRedirectUrl($redirectUrl)
            ->addCancelUrl($cancelUrl)
            ->addCloseWindow(false)
            ->addNotificationMethod();

        if ($additionalSettings = $this->settingsBuilder->build($order, $payment, $orderRequest)) {
            $paymentOptions->addSettings($additionalSettings);
        }

        $orderRequest->addPaymentOptions($paymentOptions);
    }

    /**
     * @throws NoSuchEntityException
     */
    private function getUrl(string $endPoint, int $storeId, ?array $params = null): string
    {
        return $this->storeManager->getStore($storeId)->getBaseUrl()
            . $endPoint
            . ($params ? "?" . http_build_query($params) : '');
    }
}
