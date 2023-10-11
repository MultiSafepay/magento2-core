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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\ShoppingCartBuilder;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart\Item;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Logger\Logger;
use MultiSafepay\ConnectCore\Model\Api\Validator\CustomTotalValidator;
use MultiSafepay\ConnectCore\Util\ThirdPartyPluginsUtil;
use MultiSafepay\ValueObject\Money;

class CustomTotalBuilder implements ShoppingCartBuilderInterface
{
    public const EXCLUDED_TOTALS = [
        'subtotal',
        'shipping',
        'tax',
        'grand_total',
        'fooman_surcharge_tax_after',
        'mp_reward_spent',
        'mp_reward_earn',
        'marketplace_shipping',
    ];

    /**
     * @var ThirdPartyPluginsUtil
     */
    protected $thirdPartyPluginsUtil;

    /**
     * @var CustomTotalValidator
     */
    protected $customTotalValidator;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * CustomTotalBuilder constructor.
     *
     * @param Config $config
     * @param ThirdPartyPluginsUtil $thirdPartyPluginsUtil
     * @param CartRepositoryInterface $quoteRepository
     * @param CustomTotalValidator $customTotalValidator
     * @param Logger $logger
     */
    public function __construct(
        Config $config,
        ThirdPartyPluginsUtil $thirdPartyPluginsUtil,
        CartRepositoryInterface $quoteRepository,
        CustomTotalValidator $customTotalValidator,
        Logger $logger
    ) {
        $this->config = $config;
        $this->thirdPartyPluginsUtil = $thirdPartyPluginsUtil;
        $this->customTotalValidator = $customTotalValidator;
        $this->logger = $logger;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @param OrderInterface $order
     * @param string $currency
     * @return array
     */
    public function build(OrderInterface $order, string $currency): array
    {
        $items = [];

        if (($quote = $this->getQuoteFromOrder($order)) === null) {
            return $items;
        }

        $storeId = $quote->getStoreId();
        // Merge excluded totals from config with predefined
        $customTotalsConfig = $this->config->getCustomTotals($storeId);
        $customTotalList = array_map('trim', explode(';', $customTotalsConfig));
        $excludedTotals = array_merge($customTotalList, self::EXCLUDED_TOTALS);
        $totals = array_merge(
            $quote->getTotals(),
            $this->thirdPartyPluginsUtil->getThirdPartyPluginAdditionalData($quote)
        );

        foreach ($totals as $total) {
            if (!$this->customTotalValidator->validate($total)) {
                continue;
            }

            if (!in_array($total->getCode(), $excludedTotals, true)) {
                $items[] = $this->buildItem($total, $currency, $storeId);
            }
        }

        return $items;
    }

    /**
     * @param $total
     * @param string $currency
     * @param int $storeId
     * @return Item
     */
    private function buildItem($total, string $currency, int $storeId): Item
    {
        $title = $this->getTitle($total);

        if (class_exists(\Magento\GiftCardAccount\Model\Giftcardaccount::class)) {
            if ($total->getCode() === 'giftcardaccount' && ($giftCards = $total->getGiftCards())) {
                $title = $this->getGiftCardAccountTitle($total, $giftCards);
            }
        }

        $unitPrice = $total->getAmount() ? $this->getAmount($total, $storeId) : $total->getValue();

        return (new Item())
            ->addName($title)
            ->addUnitPrice(new Money(round($unitPrice * 100, 10), $currency))
            ->addQuantity(1)
            ->addDescription($title)
            ->addMerchantItemId($total->getCode())
            ->addTaxRate($this->getTaxRate($total, $storeId));
    }

    /**
     * @param $total
     * @param array $giftCardsData
     * @return string
     */
    private function getGiftCardAccountTitle($total, array $giftCardsData): string
    {
        $couponCodes = '';

        foreach ($giftCardsData as $data) {
            $couponCodes .= $data[\Magento\GiftCardAccount\Model\Giftcardaccount::CODE] . ',';
        }

        return $this->getTitle($total) . ' (' . rtrim($couponCodes, ',') . ')';
    }

    /**
     * @param $total
     * @param int $storeId
     * @return float
     */
    private function getTaxRate($total, int $storeId): float
    {
        if (!empty((float)$total->getValue())) {
            return $total->getTaxRate() ?? round($total->getTaxAmount() / $total->getValue() * 100);
        }

        if ($this->config->useBaseCurrency($storeId)) {
            return $total->getBaseTaxRate() ??
                   ($total->getBaseAmount()
                       ? round($total->getBaseTaxAmount() / $total->getBaseAmount() * 100) : 0);
        }

        return $total->getBaseTaxRate() ??
               ($total->getAmount() ? round($total->getTaxAmount() / $total->getAmount() * 100) : 0);
    }

    /**
     * @param $total
     * @param int $storeId
     * @return float
     */
    private function getAmount($total, int $storeId): float
    {
        if ($this->config->useBaseCurrency($storeId)) {
            return (float)$total->getBaseAmount();
        }

        return (float)$total->getAmount();
    }

    /**
     * @param $total
     * @return string
     */
    private function getTitle($total): string
    {
        $title = $total->getTitle() ? : $total->getLabel();

        if ($title instanceof Phrase) {
            return (string)$title->render();
        }

        return (string)$title;
    }

    /**
     * @param OrderInterface $order
     * @return CartInterface|null
     */
    private function getQuoteFromOrder(OrderInterface $order): ?CartInterface
    {
        try {
            return $this->quoteRepository->get($order->getQuoteId());
        } catch (NoSuchEntityException $noSuchEntityException) {
            $this->logger->error(
                __(
                    'Order ID: %1, Can\'t instantiate the quote. Error: %2',
                    $order->getIncrementId(),
                    $noSuchEntityException->getMessage()
                )
            );
        }

        return null;
    }
}
