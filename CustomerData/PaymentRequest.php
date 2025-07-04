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

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Quote\Model\Quote;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\CustomerData\PaymentRequest\ApplePayRequest;
use MultiSafepay\ConnectCore\CustomerData\PaymentRequest\GooglePayRequest;
use MultiSafepay\ConnectCore\CustomerData\PaymentRequest\PaymentComponentRequest;
use MultiSafepay\ConnectCore\Util\ApiTokenUtil;
use MultiSafepay\Exception\InvalidDataInitializationException;

class PaymentRequest implements SectionSourceInterface
{
    public const PAYMENT_COMPONENT_CONTAINER_ID = 'multisafepay-payment-component';

    /**
     * @var Quote|null
     */
    private $quote = null;

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
     * @var ApiTokenUtil
     */
    private $apiTokenUtil;

    /**
     * PaymentRequest constructor.
     *
     * @param ApplePayRequest $applePayRequest
     * @param GooglePayRequest $googlePayRequest
     * @param Logger $logger
     * @param Config $config
     * @param ResolverInterface $localeResolver
     * @param PaymentComponentRequest $paymentComponentRequest
     * @param Session $session
     * @param ApiTokenUtil $apiTokenUtil
     */
    public function __construct(
        ApplePayRequest $applePayRequest,
        GooglePayRequest $googlePayRequest,
        Logger $logger,
        Config $config,
        ResolverInterface $localeResolver,
        PaymentComponentRequest $paymentComponentRequest,
        Session $session,
        ApiTokenUtil $apiTokenUtil
    ) {
        $this->applePayRequest = $applePayRequest;
        $this->googlePayRequest = $googlePayRequest;
        $this->logger = $logger;
        $this->config = $config;
        $this->localeResolver = $localeResolver;
        $this->paymentComponentRequest = $paymentComponentRequest;
        $this->session = $session;
        $this->apiTokenUtil = $apiTokenUtil;
    }

    /**
     * Load all the customer data that is necessary for
     *
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws Exception
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
            "storeId" => $storeId,
            "debug_mode" => $this->config->isDebug($storeId)
        ];

        $paymentComponentData = $this->paymentComponentRequest->create($quote);

        if ($paymentComponentData) {
            if (isset($paymentComponentData['payment_component_template_id'])) {
                $result['payment_component_template_id'] = $paymentComponentData['payment_component_template_id'];
                unset($paymentComponentData['payment_component_template_id']);
            }

            $apiTokenData = $this->apiTokenUtil->getApiTokenFromCache($quote);

            $result = array_merge(
                $result,
                [
                    "paymentComponentContainerId" => self::PAYMENT_COMPONENT_CONTAINER_ID,
                    "paymentComponentConfig" => $paymentComponentData,
                    'apiToken' => $apiTokenData['apiToken'],
                    'apiTokenLifeTime' => $apiTokenData['lifeTime']
                ]
            );

            if (isset($paymentComponentData['tokens'])) {
                $result['tokens'] = $paymentComponentData['tokens'];
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
     * @return Quote|null
     */
    private function getQuote(): ?Quote
    {
        try {
            if (!$this->quote) {
                $this->quote = $this->session->getQuote();
            }
        } catch (LocalizedException | NoSuchEntityException $exception) {
            $this->logger->logException($exception);
        }

        return $this->quote;
    }

    /**
     * Get the Store ID from the quote
     *
     * @return int|null
     * @throws NoSuchEntityException
     */
    private function getStoreIdFromQuote():?int
    {
        if (method_exists($this->getQuote(), 'getStoreId')) {
            return (int)$this->getQuote()->getStoreId();
        }

        return null;
    }
}
