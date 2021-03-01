<?php

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Model\Ui\Gateway;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Store\Model\StoreManagerInterface;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\Ui\GenericConfigProvider;

class GenericGatewayConfigProvider extends GenericConfigProvider
{
    public const CODE = 'multisafepay_genericgateway';
    public const REQUIRE_SHOPPING_CART = 'require_shopping_cart';

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
     */
    public function __construct(
        AssetRepository $assetRepository,
        Config $config,
        SdkFactory $sdkFactory,
        Session $checkoutSession,
        Logger $logger,
        ResolverInterface $localeResolver,
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
            $localeResolver
        );
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return self::CODE;
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getImage(): string
    {
        $path = $this->getImagePath();
        $imageExists = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->isFile($path);

        return $imageExists ? $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . $path : '';
    }

    /**
     * @return string
     */
    public function getImagePath(): string
    {
        return self::UPLOAD_DIR . DIRECTORY_SEPARATOR . 'generic_image.png';
    }
}
