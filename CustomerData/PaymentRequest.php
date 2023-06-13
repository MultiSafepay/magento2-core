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

namespace MultiSafepay\ConnectCore\CustomerData;

use Magento\Checkout\Model\Session;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Quote\Api\Data\CartInterface;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\Ui\GenericConfigProvider;
use MultiSafepay\ConnectCore\CustomerData\PaymentRequest\ApplePayRequest;
use MultiSafepay\ConnectCore\CustomerData\PaymentRequest\GooglePayRequest;
use MultiSafepay\ConnectCore\CustomerData\PaymentRequest\PaymentComponentRequest;
use MultiSafepay\Exception\ApiException;

class PaymentRequest implements SectionSourceInterface
{
    public const PAYMENT_COMPONENT_CONTAINER_ID = 'multisafepay-payment-component';

    /**
     * @var CartInterface|null
     */
    private $quote = null;

    /**
     * @var GenericConfigProvider
     */
    private $genericConfigProvider;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var PaymentComponentRequest
     */
    private $paymentComponentRequest;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var ApplePayRequest
     */
    private $applePayRequest;

    /**
     * @var GooglePayRequest
     */
    private $googlePayRequest;

    /**
     * PaymentRequest constructor.
     *
     * @param ApplePayRequest $applePayRequest
     * @param GenericConfigProvider $genericConfigProvider
     * @param GooglePayRequest $googlePayRequest
     * @param Logger $logger
     * @param Config $config
     * @param ResolverInterface $localeResolver
     * @param PaymentComponentRequest $paymentComponentRequest
     * @param Session $session
     */
    public function __construct(
        ApplePayRequest $applePayRequest,
        GenericConfigProvider $genericConfigProvider,
        GooglePayRequest $googlePayRequest,
        Logger $logger,
        Config $config,
        ResolverInterface $localeResolver,
        PaymentComponentRequest $paymentComponentRequest,
        Session $session
    ) {
        $this->applePayRequest = $applePayRequest;
        $this->genericConfigProvider = $genericConfigProvider;
        $this->googlePayRequest = $googlePayRequest;
        $this->logger = $logger;
        $this->config = $config;
        $this->localeResolver = $localeResolver;
        $this->paymentComponentRequest = $paymentComponentRequest;
        $this->session = $session;
    }

    /**
     * @return array|false[]
     */
    public function getSectionData(): array
    {
        $storeId = $this->getStoreIdFromQuote();
        $quote = $this->getQuote();

        $result = [
            "environment" => $this->config->isLiveMode($storeId) ? 'live' : 'test',
            "locale" => $this->localeResolver->getLocale(),
            "cartTotal" => $quote->getGrandTotal(),
            "currency" => $quote->getCurrency()->getQuoteCurrencyCode() ?? '',
            "storeId" => $storeId
        ];

        $paymentComponentData = $this->paymentComponentRequest->create($quote);

        if ($paymentComponentData) {
            try {
                $result = array_merge(
                    $result,
                    [
                        "paymentComponentContainerId" => self::PAYMENT_COMPONENT_CONTAINER_ID,
                        "paymentComponentConfig" => $paymentComponentData,
                        'apiToken' => $this->genericConfigProvider->getApiToken($storeId)
                    ]
                );
            } catch (ApiException $apiException) {
                $this->logger->logPaymentComponentException($apiException);
            }
        }

        $applePayRequest = $this->applePayRequest->create($quote);

        if ($applePayRequest !== null) {
            $result['applePayButton'] = $applePayRequest;
        }

        $googlePayRequest = $this->googlePayRequest->create($quote);

        if ($googlePayRequest !== null) {
            $result['googlePayButton'] = $googlePayRequest;
        }

        return $result;
    }

    /**
     * Retrieve the quote from the checkout session
     *
     * @return CartInterface
     */
    private function getQuote(): ?CartInterface
    {
        try {
            if (!$this->quote) {
                $this->quote = $this->session->getQuote();
            }
        } catch (LocalizedException | NoSuchEntityException $exception) {
            $this->logger->logPaymentComponentException($exception);
        }

        return $this->quote;
    }

    private function getStoreIdFromQuote():?int
    {
        if (method_exists($this->getQuote(), 'getStoreId')) {
            return (int)$this->getQuote()->getStoreId();
        }

        return null;
    }
}