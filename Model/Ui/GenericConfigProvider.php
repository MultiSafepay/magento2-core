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

namespace MultiSafepay\ConnectCore\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\Exception\InvalidApiKeyException;
use MultiSafepay\Sdk;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class GenericConfigProvider implements ConfigProviderInterface
{
    public const CODE = '';
    private const DEFAULT_CONFIG_PAYMENT_PATH = 'payment/%s';

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
     * GenericConfigProvider constructor.
     *
     * @param AssetRepository $assetRepository
     * @param Config $config
     * @param SdkFactory $sdkFactory
     * @param Session $checkoutSession
     * @param Logger $logger
     * @param ResolverInterface $localeResolver
     * @param PaymentConfig $paymentConfig
     */
    public function __construct(
        AssetRepository $assetRepository,
        Config $config,
        SdkFactory $sdkFactory,
        Session $checkoutSession,
        Logger $logger,
        ResolverInterface $localeResolver,
        PaymentConfig $paymentConfig
    ) {
        $this->assetRepository = $assetRepository;
        $this->config = $config;
        $this->sdkFactory = $sdkFactory;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->localeResolver = $localeResolver;
        $this->paymentConfig = $paymentConfig;
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
                ],
            ],
        ];
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getImage(): string
    {
        $path = 'MultiSafepay_ConnectCore::images/' . $this->getCode() . '.png';

        $this->assetRepository->createAsset($path);

        return $this->assetRepository->getUrl($path);
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
        try {
            return $this->sdkFactory->create($storeId);
        } catch (InvalidApiKeyException $invalidApiKeyException) {
            $this->logger->logInvalidApiKeyException($invalidApiKeyException);

            return null;
        } catch (ApiException $apiException) {
            $orderId = $this->checkoutSession->getLastRealOrder()->getIncrementId();
            $this->logger->logGetIssuersApiException($orderId, $apiException);

            return null;
        }
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getApiToken(?int $storeId = null): string
    {
        if ($multiSafepaySdk = $this->getSdk($storeId)) {
            try {
                return $multiSafepaySdk->getApiTokenManager()->get()->getApiToken();
            } catch (ClientExceptionInterface $clientException) {
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
            $this->checkoutSession->getQuote()->getStoreId()
        );
    }

    /**
     * @param int|null $storeId
     * @return array
     */
    public function getPaymentConfig(int $storeId = null): array
    {
        return (array)$this->config->getValueByPath(
            sprintf(self::DEFAULT_CONFIG_PAYMENT_PATH, $this->getCode()),
            $storeId
        );
    }
}
