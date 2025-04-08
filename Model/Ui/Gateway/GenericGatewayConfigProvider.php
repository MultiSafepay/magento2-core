<?php

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Model\Ui\Gateway;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\Ui\GenericConfigProvider;
use MultiSafepay\ConnectCore\Util\CheckoutFieldsUtil;
use MultiSafepay\ConnectCore\Util\GenericGatewayUtil;
use MultiSafepay\ConnectCore\Util\JsonHandler;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class GenericGatewayConfigProvider extends GenericConfigProvider
{
    public const CODE = 'multisafepay_genericgateway';
    public const REQUIRE_SHOPPING_CART = 'require_shopping_cart';

    /**
     * @var GenericGatewayUtil
     */
    private $genericGatewayUtil;

    /**
     * GenericGatewayConfigProvider constructor.
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
     * @param GenericGatewayUtil $genericGatewayUtil
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
        GenericGatewayUtil $genericGatewayUtil
    ) {
        $this->genericGatewayUtil = $genericGatewayUtil;
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
                'image' => $this->genericGatewayUtil->getGenericFullImagePath($gatewayCode),
                'is_preselected' => $this->isPreselectedByCode($gatewayCode),
                'instructions' => $this->getInstructions()
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

        foreach (GenericGatewayUtil::GENERIC_CONFIG_PATHS as $path) {
            $genericList[] = (array)$this->config->getValueByPath($path, $storeId);
        }

        $genericList = array_merge(...$genericList);

        return $genericList ? array_filter(array_keys($genericList), function ($key) {
            return strpos($key, self::CODE . '_') === 0;
        }) : [];
    }
}
