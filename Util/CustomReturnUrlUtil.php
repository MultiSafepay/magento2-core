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

namespace MultiSafepay\ConnectCore\Util;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Api\StoreRepositoryInterface;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\SecureToken;

class CustomReturnUrlUtil
{
    public const USE_CUSTOM_URL_CONFIG_PATH = 'use_custom_return_url';

    public const SUCCESS_URL_TYPE_NAME = 'success';
    public const CANCEL_URL_TYPE_NAME = 'cancel';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var QuoteIdToMaskedQuoteIdInterface|null
     */
    private $quoteIdToMaskedQuoteId;

    /**
     * @var SecureToken
     */
    private $secureToken;

    /**
     * CustomReturnUrlUtil constructor.
     *
     * @param Config $config
     * @param SecureToken $secureToken
     * @param StoreRepositoryInterface $storeRepository
     * @param Logger $logger
     * @param ProductMetadataInterface $productMetadata
     */
    public function __construct(
        Config $config,
        SecureToken $secureToken,
        StoreRepositoryInterface $storeRepository,
        Logger $logger,
        ProductMetadataInterface $productMetadata
    ) {
        $this->config = $config;
        $this->secureToken = $secureToken;
        $this->storeRepository = $storeRepository;
        $this->logger = $logger;
        $this->quoteIdToMaskedQuoteId = version_compare($productMetadata->getVersion(), '2.3', '>=')
            ? ObjectManager::getInstance()->get(QuoteIdToMaskedQuoteIdInterface::class) : null;
    }

    /**
     * @param OrderInterface $order
     * @param array $transactionParameters
     * @param string $customUrlType
     * @return string|null
     * @throws \Exception
     */
    public function getCustomReturnUrlByType(
        OrderInterface $order,
        array $transactionParameters,
        string $customUrlType = self::CANCEL_URL_TYPE_NAME
    ): ?string {
        $storeId = $order->getStoreId();

        if ($this->config->getAdvancedValue(self::USE_CUSTOM_URL_CONFIG_PATH, $storeId)) {
            try {
                $customUrl = $this->config->getAdvancedValue(
                    $this->getCustomUrlConfigPathByType($customUrlType),
                    $storeId
                );

                return $customUrl ? $this->buildCustomUrl($customUrl, $order, $transactionParameters) : null;
            } catch (NoSuchEntityException $noSuchEntityException) {
                $this->logger->error(
                    __(
                        'Order ID: %1, Can\'t redirect to a custom url. Error: %2',
                        $order->getIncrementId(),
                        $noSuchEntityException->getMessage()
                    )
                );
            }
        }

        return null;
    }

    /**
     * @param string $customUrlType
     * @return string
     */
    private function getCustomUrlConfigPathByType(string $customUrlType): string
    {
        return 'custom_' . $customUrlType . '_return_url';
    }

    /**
     * @param string $urlString
     * @param Order $order
     * @param array $transactionParameters
     * @return string
     * @throws NoSuchEntityException
     */
    private function buildCustomUrl(string $urlString, Order $order, array $transactionParameters): string
    {
        $storeId = $order->getStoreId();
        $orderStore = $this->storeRepository->getById($storeId);
        $availableCustomVariables = [
            '{{order.increment_id}}' => $order->getIncrementId(),
            '{{order.order_id}}' => $order->getEntityId(),
            '{{quote.quote_id}}' => $order->getQuoteId(),
            '{{payment.code}}' => $order->getPayment()->getMethod(),
            '{{payment.transaction_id}}' => $transactionParameters['transactionid'] ?? '',
            '{{store.unsecure_base_url}}' => $orderStore->getBaseUrl(UrlInterface::URL_TYPE_WEB),
            '{{store.secure_base_url}}' => $orderStore->getBaseUrl(UrlInterface::URL_TYPE_WEB, true),
            '{{store.code}}' => $orderStore->getCode(),
            '{{store.store_id}}' => $orderStore->getId(),
            '{{secure_token}}' => $transactionParameters['secureToken'] ?? $this->secureToken->generate((string)
                $order->getRealOrderId()),
        ];

        if ($this->quoteIdToMaskedQuoteId) {
            $availableCustomVariables['{{quote.masked_id}}'] =
                $this->quoteIdToMaskedQuoteId->execute((int)$order->getQuoteId());
        }

        foreach ($availableCustomVariables as $var => $value) {
            $urlString = str_replace($var, $value, $urlString);
        }

        return $urlString;
    }
}
