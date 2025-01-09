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

namespace MultiSafepay\ConnectCore\CustomerData\PaymentRequest;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\Data\CartInterface;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Model\Ui\ConfigProviderPool;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\AfterpayConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\AmexConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\BillinkConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\BnplinstmConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\BnplmfConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\CreditCardConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\EinvoicingConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\In3ConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\MaestroConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\MastercardConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\VisaConfigProvider;
use MultiSafepay\ConnectCore\Util\RecurringTokensUtil;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\ZiniaConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\In3B2bConfigProvider;

class PaymentComponentRequest
{
    private const PAYMENT_COMPONENT_METHODS = [
        AmexConfigProvider::CODE,
        MaestroConfigProvider::CODE,
        MastercardConfigProvider::CODE,
        VisaConfigProvider::CODE,
        CreditCardConfigProvider::CODE,
        BnplinstmConfigProvider::CODE,
        ZiniaConfigProvider::CODE,
        BnplmfConfigProvider::CODE,
        In3B2bConfigProvider::CODE,
        AfterpayConfigProvider::CODE,
        EinvoicingConfigProvider::CODE,
        In3ConfigProvider::CODE,
        BillinkConfigProvider::CODE
    ];

    /**
     * @var UrlInterface
     */
    protected $url;

    /**
     * @var ConfigProviderPool
     */
    protected $configProviderPool;

    /**
     * @var RecurringTokensUtil
     */
    private $recurringTokensUtil;

    /**
     * @var Config
     */
    private $config;

    /**
     * PaymentConfig constructor.
     *
     * @param ConfigProviderPool $configProviderPool
     * @param UrlInterface $url
     * @param RecurringTokensUtil $recurringTokensUtil
     * @param Config $config
     */
    public function __construct(
        ConfigProviderPool $configProviderPool,
        UrlInterface $url,
        RecurringTokensUtil $recurringTokensUtil,
        Config $config
    ) {
        $this->configProviderPool = $configProviderPool;
        $this->url = $url;
        $this->recurringTokensUtil = $recurringTokensUtil;
        $this->config = $config;
    }

    /**
     * Create the payment component request data
     *
     * @param CartInterface|null $quote
     * @return array
     * @throws LocalizedException
     */
    public function create(?CartInterface $quote): array
    {
        if ($quote === null) {
            return [];
        }

        $result = [];
        $paymentComponentTemplateId = $this->config->getPaymentComponentTemplateId($quote->getStoreId());

        foreach (self::PAYMENT_COMPONENT_METHODS as $methodCode) {
            $configProvider = $this->configProviderPool->getConfigProviderByCode($methodCode);

            if (!$configProvider) {
                continue;
            }

            $paymentConfig = $configProvider->getPaymentConfig((int)$quote->getStoreId());

            if (!$paymentConfig) {
                continue;
            }

            // Don't need to add payment component data if it is disabled
            if (!$this->isPaymentComponentEnabled($paymentConfig)) {
                continue;
            }

            $result[$methodCode] = [
                "paymentMethod" => $methodCode,
                "gatewayCode" => $paymentConfig['gateway_code'],
                "paymentType" => $paymentConfig['payment_type'],
                "additionalInfo" => $configProvider->getConfig()['payment'][$methodCode] ?? [],
            ];

            if (!isset($result[$methodCode]['tokens'])) {
                $result[$methodCode]['tokens'] = $this->getTokens($quote, $paymentConfig);
            }

            if (!isset($result['payment_component_template_id']) && $paymentComponentTemplateId) {
                $result['payment_component_template_id'] = $paymentComponentTemplateId;
            }
        }

        return $result;
    }

    /**
     * Get the customer recurring tokens from the API if needed
     *
     * @param CartInterface $quote
     * @param array $paymentConfig
     * @return array|null
     */
    private function getTokens(CartInterface $quote, array $paymentConfig): ?array
    {
        // Don't need to add tokens if the customer is a guest
        if ($quote->getCustomerIsGuest()) {
            return null;
        }

        // Don't need to add tokens if tokenization is turned off
        if (isset($paymentConfig['tokenization']) && !$paymentConfig['tokenization']) {
            return null;
        }

        if (isset($paymentConfig['tokenization']) && (bool)$paymentConfig['tokenization'] === true) {
            return $this->recurringTokensUtil->getListByGatewayCode(
                (string)$quote->getCustomer()->getId(),
                $paymentConfig,
                (int)$quote->getStoreId() ?? null
            );
        }

        return null;
    }

    /**
     * Check if payment component is enabled
     *
     * @param array $paymentConfig
     * @return bool
     */
    private function isPaymentComponentEnabled(array $paymentConfig): bool
    {
        if (!isset($paymentConfig['payment_type'], $paymentConfig['active'])) {
            return false;
        }

        if (!$paymentConfig['active']) {
            return false;
        }

        if ($paymentConfig['payment_type'] === 'payment_component') {
            return true;
        }

        return false;
    }
}
