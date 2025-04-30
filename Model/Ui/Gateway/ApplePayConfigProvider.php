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

namespace MultiSafepay\ConnectCore\Model\Ui\Gateway;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use MultiSafepay\Api\Wallets\ApplePay\MerchantSessionRequest;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\Ui\GenericConfigProvider;
use MultiSafepay\ConnectCore\Util\CheckoutFieldsUtil;
use MultiSafepay\ConnectCore\Util\JsonHandler;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ApplePayConfigProvider extends GenericConfigProvider
{
    public const CODE = 'multisafepay_applepay';
    public const APPLE_PAY_BUTTON_CONFIG_PATH = 'direct_button';
    public const APPLE_PAY_BUTTON_ID = 'multisafepay-apple-pay-button';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var MerchantSessionRequest
     */
    private $merchantSessionRequest;

    /**
     * ApplePayConfigProvider constructor.
     *
     * @param AssetRepository $assetRepository
     * @param Config $config
     * @param SdkFactory $sdkFactory
     * @param Session $checkoutSession
     * @param Logger $logger
     * @param ResolverInterface $localeResolver
     * @param PaymentConfig $paymentConfig
     * @param WriterInterface $configWriter
     * @param JsonHandler $jsonHandler
     * @param CheckoutFieldsUtil $checkoutFieldsUtil
     * @param StoreManagerInterface $storeManager
     * @param MerchantSessionRequest $merchantSessionRequest
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        AssetRepository $assetRepository,
        Config $config,
        SdkFactory $sdkFactory,
        Session $checkoutSession,
        Logger $logger,
        ResolverInterface $localeResolver,
        PaymentConfig $paymentConfig,
        WriterInterface $configWriter,
        JsonHandler $jsonHandler,
        CheckoutFieldsUtil $checkoutFieldsUtil,
        StoreManagerInterface $storeManager,
        MerchantSessionRequest $merchantSessionRequest
    ) {
        $this->storeManager = $storeManager;
        $this->merchantSessionRequest = $merchantSessionRequest;
        parent::__construct(
            $assetRepository,
            $config,
            $sdkFactory,
            $checkoutSession,
            $logger,
            $localeResolver,
            $paymentConfig,
            $configWriter,
            $jsonHandler,
            $checkoutFieldsUtil
        );
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isApplePayActive(?int $storeId = null): bool
    {
        return (bool)$this->getPaymentConfig($storeId)[self::APPLE_PAY_BUTTON_CONFIG_PATH];
    }

    /**
     * @param int|null $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getApplePayMerchantSessionUrl(?int $storeId = null): string
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore($storeId);

        return $store->getUrl('multisafepay/apple/session');
    }

    /**
     * @param array $requestData
     * @param int|null $storeId
     * @return string
     */
    public function createApplePayMerchantSession(array $requestData, ?int $storeId = null): string
    {
        if ($multiSafepaySdk = $this->getSdk($storeId)) {
            try {
                return $multiSafepaySdk->getWalletManager()
                    ->createApplePayMerchantSession($this->merchantSessionRequest->addData($requestData))
                    ->getMerchantSession();
            } catch (ClientExceptionInterface $clientException) {
                return '';
            }
        }

        return '';
    }
}
