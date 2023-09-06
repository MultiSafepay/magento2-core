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

use Magento\Framework\UrlInterface;
use Magento\Quote\Api\Data\CartInterface;
use MultiSafepay\ConnectCore\Model\Ui\ConfigProviderPool;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\AmexConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\BnplinstmConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\CreditCardConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\MaestroConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\MastercardConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\VisaConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\ZiniaConfigProvider;

class PaymentComponentRequest
{
    private const PAYMENT_COMPONENT_METHODS = [
        AmexConfigProvider::CODE,
        MaestroConfigProvider::CODE,
        MastercardConfigProvider::CODE,
        VisaConfigProvider::CODE,
        CreditCardConfigProvider::CODE,
        BnplinstmConfigProvider::CODE,
        ZiniaConfigProvider::CODE
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
     * PaymentConfig constructor.
     *
     * @param ConfigProviderPool $configProviderPool
     * @param UrlInterface $url
     */
    public function __construct(
        ConfigProviderPool $configProviderPool,
        UrlInterface $url
    ) {
        $this->configProviderPool = $configProviderPool;
        $this->url = $url;
    }

    /**
     * Create the payment component request data
     *
     * @param CartInterface|null $quote
     * @return array
     */
    public function create(?CartInterface $quote): array
    {
        if ($quote === null) {
            return [];
        }

        $result = [];

        foreach (self::PAYMENT_COMPONENT_METHODS as $methodCode) {
            $configProvider = $this->configProviderPool->getConfigProviderByCode($methodCode);

            if (!$configProvider) {
                continue;
            }

            $paymentConfig = $configProvider->getPaymentConfig((int)$quote->getStoreId());

            if (!$paymentConfig) {
                continue;
            }

            if ($this->isPaymentComponentEnabled($paymentConfig)) {
                $result[$methodCode] = [
                    "paymentMethod" => $methodCode,
                    "gatewayCode" => $paymentConfig['gateway_code'],
                    "paymentType" => $paymentConfig['payment_type'],
                    "additionalInfo" => $configProvider->getConfig()['payment'][$methodCode] ?? [],
                ];

                if (isset($paymentConfig['tokenization']) && (bool)$paymentConfig['tokenization'] === true) {
                    $result[$methodCode]['customerReference'] = $this->getCustomerReference($quote);
                }
            }
        }

        return $result;
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

    /**
     * Get the customer reference which is needed for tokenization inside component
     *
     * @param CartInterface $quote
     * @return int|null
     */
    private function getCustomerReference(CartInterface $quote): ?int
    {
        if ($quote->getCustomerIsGuest()) {
            return null;
        }

        return (int)$quote->getCustomer()->getId();
    }
}
