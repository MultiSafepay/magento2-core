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

namespace MultiSafepay\ConnectCore\Cron;

use Exception;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\StoreManagerInterface;
use MultiSafepay\Api\PaymentMethods\PaymentMethod;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Factory\SdkFactory;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\AlipayConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\BankTransferConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\CreditCardConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\DirectBankTransferConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\DirectDebitConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\GiropayConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\INGHomePayConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\PayafterConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\SantanderConfigProvider;
use Psr\Http\Client\ClientExceptionInterface;

class UpdatePaymentIcons
{
    public const UNSUPPORTED_PAYMENT_METHODS = [
        // Payment methods that have multiple icons based on locale
        DirectDebitConfigProvider::CODE,
        BankTransferConfigProvider::CODE,
        PayafterConfigProvider::CODE,

        // Payment methods that give the option for alternative icons
        CreditCardConfigProvider::CODE,

        // Payment methods that are removed
        INGHomePayConfigProvider::CODE,
        SantanderConfigProvider::CODE,
        DirectBankTransferConfigProvider::CODE,
        GiropayConfigProvider::CODE,
        AlipayConfigProvider::CODE
    ];

    /**
     * @var SdkFactory
     */
    private $sdkFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var WriterInterface
     */
    private $configWriter;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param WriterInterface $configWriter
     * @param SdkFactory $sdkFactory
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     * @param Config $config
     */
    public function __construct(
        WriterInterface $configWriter,
        SdkFactory $sdkFactory,
        StoreManagerInterface $storeManager,
        Logger $logger,
        Config $config
    ) {
        $this->configWriter = $configWriter;
        $this->sdkFactory = $sdkFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Retrieve the latest payment icons and store them in the configuration of each gateway
     * Deliberately does not use the store ID, since support for multiple locales is not yet supported
     *
     * @throws Exception
     * @throws ClientExceptionInterface
     */
    public function execute()
    {
        $supportedPaymentMethods = $this->getSupportedPaymentMethods();
        $paymentMethods = $this->getPaymentMethods();

        /** @var PaymentMethod $paymentMethod */
        foreach ($paymentMethods as $paymentMethod) {
            foreach ($supportedPaymentMethods as $supportedPaymentMethod => $values) {
                if ($paymentMethod->getId() === $values['gateway_code']) {
                    // Save the default icon URL
                    $this->configWriter->save(
                        'payment/' . $supportedPaymentMethod . '/icon_url_default',
                        $paymentMethod->getMediumIconUrl()
                    );

                    // Save the vector icon URL
                    $this->configWriter->save(
                        'payment/' . $supportedPaymentMethod . '/icon_url_svg',
                        $paymentMethod->getVectorIconUrl()
                    );

                    if (strpos($supportedPaymentMethod, '_recurring') !== false) {
                        continue;
                    }

                    break;
                }
            }
        }
    }

    /**
     * Retrieve all the MultiSafepay payment methods
     *
     * @return array
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    private function getPaymentMethods(): array
    {
        // Using the default store view, since different payment icons based on locale is currently not supported
        $store = $this->storeManager->getDefaultStoreView();
        $sdk = $this->sdkFactory->create((int)$store->getId());

        return $sdk->getPaymentMethodManager()->getPaymentMethods();
    }

    /**
     * Retrieve all the supported MultiSafepay payment methods
     *
     * @return array
     */
    private function getSupportedPaymentMethods(): array
    {
        $paymentMethods = (array)$this->config->getValueByPath('payment');

        foreach ($paymentMethods as $paymentMethod => $values) {
            if (!array_key_exists('is_multisafepay', $values)) {
                unset($paymentMethods[$paymentMethod]);
            }

            if (in_array($paymentMethod, self::UNSUPPORTED_PAYMENT_METHODS)) {
                unset($paymentMethods[$paymentMethod]);
            }

            if (!isset($values['gateway_code'])) {
                unset($paymentMethods[$paymentMethod]);
            }
        }

        return $paymentMethods;
    }
}
