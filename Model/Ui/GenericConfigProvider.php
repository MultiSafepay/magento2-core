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

namespace MultiSafepay\ConnectCore\Model\Ui;

use Exception;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Store\Model\ScopeInterface;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Util\CheckoutFieldsUtil;
use MultiSafepay\ConnectCore\Util\JsonHandler;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\Exception\InvalidApiKeyException;
use MultiSafepay\Exception\InvalidArgumentException;
use MultiSafepay\Exception\InvalidDataInitializationException;
use MultiSafepay\Sdk;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class GenericConfigProvider implements ConfigProviderInterface
{
    public const CODE = '';
    private const DEFAULT_CONFIG_PAYMENT_PATH = 'payment/%s';
    private const GATEWAY_CODE = 'gateway_code';
    protected const IMAGE_PATH = 'MultiSafepay_ConnectCore::images/';

    /**
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @var ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var SdkFactory
     */
    private $sdkFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var PaymentConfig
     */
    private $paymentConfig;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var JsonHandler
     */
    private $jsonHandler;

    /**
     * @var CheckoutFieldsUtil
     */
    protected $checkoutFieldsUtil;

    /**
     * GenericConfigProvider constructor.
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
        CheckoutFieldsUtil $checkoutFieldsUtil
    ) {
        $this->assetRepository = $assetRepository;
        $this->config = $config;
        $this->sdkFactory = $sdkFactory;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->localeResolver = $localeResolver;
        $this->paymentConfig = $paymentConfig;
        $this->configWriter = $configWriter;
        $this->jsonHandler = $jsonHandler;
        $this->checkoutFieldsUtil = $checkoutFieldsUtil;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     * @throws LocalizedException
     */
    public function getConfig(): array
    {
        return [
            'payment' => [
                $this->getCode() => [
                    'image' => $this->getImage(),
                    'is_preselected' => $this->isPreselected(),
                    'transaction_type' => $this->getTransactionType(),
                    'instructions' => $this->getInstructions(),
                ],
            ],
        ];
    }

    /**
     * Retrieve the payment gateway image
     *
     * @return string
     * @throws LocalizedException
     */
    public function getImage(): string
    {
        $url = '';
        $storeId = $this->getStoreIdFromCheckoutSession();

        if ($this->config->getIconType($storeId) === 'default') {
            $url = $this->getPaymentConfig()['icon_url_default'] ?? '';
        }

        if ($this->config->getIconType($storeId) === 'svg') {
            $url = $this->getPaymentConfig()['icon_url_svg'] ?? '';
        }

        if (empty($url)) {
            $path = self::IMAGE_PATH . $this->getCode() . '.png';

            $this->assetRepository->createAsset($path);

            return $this->assetRepository->getUrl($path);
        }

        return $url;
    }

    /**
     * Get the gateway image path
     *
     * @param string $gatewayCode
     * @return string
     */
    public function getImagePath(string $gatewayCode): string
    {
        $extension = '.png';
        $locale = 'en';
        $storeId = $this->getStoreIdFromCheckoutSession();

        if ($this->config->getIconType($storeId) === 'svg') {
            $extension = '.svg';
        }

        if ($this->localeResolver->getLocale() === 'nl_NL') {
            $locale = 'nl';
        }

        return 'MultiSafepay_ConnectCore::images/' . $gatewayCode . '-' . $locale . $extension;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return static::CODE;
    }

    /**
     * @return bool
     */
    public function isPreselected(): bool
    {
        return $this->getCode() === $this->config->getPreselectedMethod();
    }

    /**
     * @param int|null $storeId
     * @return Sdk|null
     */
    public function getSdk(?int $storeId = null): ?Sdk
    {
        if ($storeId === null) {
            $storeId = $this->getStoreIdFromCheckoutSession();
        }

        try {
            return $this->sdkFactory->create($storeId);
        } catch (InvalidApiKeyException $invalidApiKeyException) {
            $this->logger->logInvalidApiKeyException($invalidApiKeyException);

            return null;
        } catch (ApiException $apiException) {
            $this->logger->logException($apiException);

            return null;
        }
    }

    /**
     * @param int|null $storeId
     * @return string
     * @throws Exception
     */
    public function getApiToken(?int $storeId = null): string
    {
        if ($multiSafepaySdk = $this->getSdk($storeId)) {
            try {
                return $multiSafepaySdk->getApiTokenManager()->get()->getApiToken();
            } catch (ClientExceptionInterface | ApiException | InvalidDataInitializationException $exception) {
                $this->logger->logExceptionForApiToken($exception);

                return '';
            }
        }

        return '';
    }

    /**
     * @return string
     */
    public function getPaymentJsComponent(): string
    {
        return 'MultiSafepay_ConnectFrontend/js/view/payment/method-renderer';
    }

    /**
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getTransactionType(): string
    {
        $this->paymentConfig->setMethodCode($this->getCode());

        return (string)$this->paymentConfig->getValue(
            'transaction_type',
            $this->getStoreIdFromCheckoutSession()
        );
    }

    /**
     * Get the payment type
     *
     * @return string
     */
    public function getPaymentType(): string
    {
        $this->paymentConfig->setMethodCode($this->getCode());

        return (string)$this->paymentConfig->getValue(
            'payment_type',
            $this->getStoreIdFromCheckoutSession()
        );
    }

    /**
     * @param int|null $storeId
     * @return array
     */
    public function getPaymentConfig(?int $storeId = null): array
    {
        return (array)$this->config->getValueByPath(
            sprintf(self::DEFAULT_CONFIG_PAYMENT_PATH, $this->getCode()),
            $storeId
        );
    }

    /**
     * @return string
     */
    public function getGatewayCode(): string
    {
        return (string)$this->config->getValueByPath(
            sprintf(
                self::DEFAULT_CONFIG_PAYMENT_PATH,
                $this->getCode() . DIRECTORY_SEPARATOR . self::GATEWAY_CODE
            )
        );
    }

    /**
     * @param int|null $storeId
     * @return array
     */
    public function getAccountData(?int $storeId = null): array
    {
        $accountData = $this->jsonHandler->readJSON($this->config->getAccountData($storeId));

        if (!$accountData) {
            try {
                $accountData = $this->sdkFactory->create((int)$storeId)
                    ->getAccountManager()
                    ->get()
                    ->getData();
            } catch (ApiException $apiException) {
                $this->logger->logException($apiException);
            } catch (InvalidApiKeyException $invalidApiKeyException) {
                $this->logger->logInvalidApiKeyException($invalidApiKeyException);
            } catch (ClientExceptionInterface $clientException) {
                $this->logger->logClientException('', $clientException);
            } catch (InvalidDataInitializationException $invalidDataInitializationException) {
                $this->logger->logException($invalidDataInitializationException);
            }

            $this->configWriter->save(
                sprintf(Config::DEFAULT_PATH_PATTERN, Config::MULTISAFEPAY_ACCOUNT_DATA),
                $this->jsonHandler->convertToJSON($accountData),
                ScopeInterface::SCOPE_STORE,
                $storeId ?: 0
            );
        }

        return $accountData;
    }

    /**
     * Get Issuers if available
     *
     * @return array
     */
    public function getIssuers(): array
    {
        $issuers = [];

        $paymentConfig = $this->getPaymentConfig($this->getStoreIdFromCheckoutSession());

        if (!$paymentConfig) {
            $this->logger->debug('Payment config not found when retrieving issuers');
        }

        if (!isset($paymentConfig['active'])) {
            $this->logger->debug('Could not check if payment method is activated when retrieving issuers');
        }

        if (!$paymentConfig['active']) {
            // Payment method not activated, issuer request not sent
            return [];
        }

        if ($multiSafepaySdk = $this->getSdk()) {
            try {
                $issuerListing = $multiSafepaySdk->getIssuerManager()->getIssuersByGatewayCode($this->getGatewayCode());
                foreach ($issuerListing as $issuer) {
                    $issuers[] = [
                        'code' => $issuer->getCode(),
                        'description' => $issuer->getDescription(),
                    ];
                }
            } catch (InvalidArgumentException $invalidArgumentException) {
                $this->logger->logException($invalidArgumentException);
            } catch (ClientExceptionInterface $clientException) {
                $this->logger->logException($clientException);
            } catch (ApiException $apiException) {
                $this->logger->logException($apiException);
            }
        }

        return $issuers;
    }

    /**
     * Return the store ID from the Checkout Session
     *
     * @return int|null
     */
    protected function getStoreIdFromCheckoutSession(): ?int
    {
        try {
            $storeId = $this->checkoutSession->getQuote()->getStoreId();
        } catch (LocalizedException $localizedException) {
            $this->logger->logException($localizedException);
            return null;
        }

        return $storeId;
    }

    /**
     * Get the payment instructions
     *
     * @return string
     */
    protected function getInstructions(): string
    {
        $this->paymentConfig->setMethodCode($this->getCode());

        return (string)$this->paymentConfig->getValue('instructions', $this->getStoreIdFromCheckoutSession());
    }
}
