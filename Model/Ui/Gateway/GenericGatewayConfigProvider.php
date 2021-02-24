<?php

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Model\Ui\Gateway;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Store\Model\StoreManagerInterface;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Model\Ui\GenericConfigProvider;

class GenericGatewayConfigProvider extends GenericConfigProvider
{
    public const CODE = 'multisafepay_genericgateway';
    public const REQUIRE_SHOPPING_CART = 'require_shopping_cart';
    public const MULTISAFEPAY_LIST_CONFIG_PATH = 'multisafepay_gateways';
    public const CONFIG_IMAGE_PATH = 'multisafepay_gateways/%s/gateway_image';

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
     * @var Config
     */
    private $config;

    /**
     * GenericGatewayConfigProvider constructor.
     *
     * @param AssetRepository $assetRepository
     * @param Config $config
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        AssetRepository $assetRepository,
        Config $config,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
        $this->filesystem = $filesystem;
        $this->config = $config;
        parent::__construct($assetRepository, $config);
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

        foreach ($this->getGenericGatewaysList() as $gatewayCode) {
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
        $configImagePath = $this->config->getValueByPath(sprintf(self::CONFIG_IMAGE_PATH, $gatewayCode));

        return self::UPLOAD_DIR . DIRECTORY_SEPARATOR . $configImagePath;
    }

    /**
     * @param null $storeId
     * @return array
     */
    public function getGenericGatewaysList($storeId = null): array
    {
        $gatewaysList = $this->getGenericList($storeId);

        return $gatewaysList && is_array($gatewaysList) ? array_filter(array_keys($gatewaysList), function ($key) {
            return strpos($key, self::CODE . '_') === 0;
        }) : [];
    }

    /**
     * @return string
     */
    public function getPaymentJsComponent(): string
    {
        return 'MultiSafepay_ConnectFrontend/js/view/payment/gateway/genericgateway';
    }
}
