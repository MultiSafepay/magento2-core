<?php

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Model\Ui\Gateway;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Store\Model\StoreManagerInterface;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\Ui\GenericConfigProvider;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class GenericGatewayConfigProvider extends GenericConfigProvider
{
    public const CODE = 'multisafepay_genericgateway';
    public const REQUIRE_SHOPPING_CART = 'require_shopping_cart';
    public const GENERIC_CONFIG_IMAGE_PATH = '%s/gateway_image';
    public const GENERIC_CONFIG_PATHS = [
        'multisafepay_gateways',
        'multisafepay_giftcards'
    ];

    /**
     * The tail part of directory path for uploading the logo
     */
    public const UPLOAD_DIR = 'multisafepay/genericgateway';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * GenericGatewayConfigProvider constructor.
     *
     * @param AssetRepository $assetRepository
     * @param Config $config
     * @param SdkFactory $sdkFactory
     * @param Session $checkoutSession
     * @param Logger $logger
     * @param ResolverInterface $localeResolver
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     * @param PaymentConfig $paymentConfig
     */
    public function __construct(
        AssetRepository $assetRepository,
        Config $config,
        SdkFactory $sdkFactory,
        Session $checkoutSession,
        Logger $logger,
        ResolverInterface $localeResolver,
        PaymentConfig $paymentConfig,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
        $this->filesystem = $filesystem;
        parent::__construct(
            $assetRepository,
            $config,
            $sdkFactory,
            $checkoutSession,
            $logger,
            $localeResolver,
            $paymentConfig
        );
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     * @throws LocalizedException
     */
    public function getConfig(): array
    {
        $configData = [];

        foreach ($this->getGenericList() as $gatewayCode) {
            $configData[$gatewayCode] = [
                'image' => $this->getGenericFullImagePath($gatewayCode),
                'is_preselected' => $this->isPreselectedByCode($gatewayCode)
            ];
        }

        return ['payment' => $configData];
    }

    /**
     * @param string $gatewayCode
     * @return bool
     */
    public function isPreselectedByCode(string $gatewayCode): bool
    {
        return $gatewayCode === $this->config->getPreselectedMethod();
    }

    /**
     * @param string $gatewayCode
     * @return string
     * @throws NoSuchEntityException
     */
    public function getGenericFullImagePath(string $gatewayCode): string
    {
        $path = $this->getImagePath($gatewayCode);
        $imageExists = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->isFile($path);

        return $imageExists ? $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . $path : '';
    }

    /**
     * @param string $gatewayCode
     * @return string
     */
    public function getImagePath(string $gatewayCode): string
    {
        foreach (self::GENERIC_CONFIG_PATHS as $path) {
            $configFullPath = $path . DIRECTORY_SEPARATOR . self::GENERIC_CONFIG_IMAGE_PATH;

            if ($configImagePath = $this->config->getValueByPath(sprintf($configFullPath, $gatewayCode))) {
                return self::UPLOAD_DIR . DIRECTORY_SEPARATOR . $configImagePath;
            }
        }

        return '';
    }

    /**
     * @param string $paymentCode
     * @return bool
     */
    public function isMultisafepayGenericMethod(string $paymentCode): bool
    {
        return strpos($paymentCode, self::CODE . '_') !== false;
    }

    /**
     * @param null $storeId
     * @return array
     */
    public function getGenericList($storeId = null): array
    {
        $genericList = [];

        foreach (self::GENERIC_CONFIG_PATHS as $path) {
            $genericList[] = (array)$this->config->getValueByPath($path, $storeId);
        }
        $genericList = array_merge(...$genericList);

        return $genericList ? array_filter(array_keys($genericList), function ($key) {
            return strpos($key, self::CODE . '_') === 0;
        }) : [];
    }
}
