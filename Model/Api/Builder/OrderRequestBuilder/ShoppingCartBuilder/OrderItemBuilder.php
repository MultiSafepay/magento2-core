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

use Exception;
use Magento\Bundle\Model\Product\Price;
use Magento\Catalog\Model\Product\Type;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\Item;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart\Item as TransactionItem;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\ShoppingCartBuilder\OrderItemBuilder\WeeeTaxBuilder;
use MultiSafepay\ConnectCore\Util\PriceUtil;
use MultiSafepay\Exception\InvalidArgumentException;
use MultiSafepay\ValueObject\Money;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderItemBuilder implements ShoppingCartBuilderInterface
{
    /**
     * @var PriceUtil
     */
    private $priceUtil;

    /**
     * @var WeeeTaxBuilder
     */
    private $weeeTaxBuilder;

    /**
     * @var Config
     */
    private $config;

    /**
     * OrderItemBuilder constructor.
     *
     * @param PriceUtil $priceUtil
     * @param WeeeTaxBuilder $weeeTaxBuilder
     * @param Config $config
     */
    public function __construct(
        PriceUtil $priceUtil,
        WeeeTaxBuilder $weeeTaxBuilder,
        Config $config
    ) {
        $this->priceUtil = $priceUtil;
        $this->weeeTaxBuilder = $weeeTaxBuilder;
        $this->config = $config;
    }

    /**
     * Build the order items
     *
     * @param OrderInterface $order
     * @param string $currency
     * @throws InvalidArgumentException
     * @return array
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function build(OrderInterface $order, string $currency): array
    {
        $storeId = $order->getStoreId();
        $items = [];
        $orderItems = $order->getItems();

        foreach ($orderItems as $item) {
            if (!$this->canAddToShoppingCart($item)) {
                continue;
            }

            $unitPrice = $this->priceUtil->getUnitPrice($item, $storeId);
            $items[] = (new TransactionItem())
                ->addName($this->getName($item, (string)$order->getDiscountDescription()))
                ->addUnitPrice(new Money(round($unitPrice * 100, 10), $currency))
                ->addQuantity((float)$item->getQtyOrdered())
                ->addDescription($this->getDescription($item))
                ->addMerchantItemId($this->getMerchantItemId($item))
                ->addTaxRate($this->getTaxRate($item));
        }

        return $this->weeeTaxBuilder->addWeeeTaxToItems($items, $orderItems, (int)$storeId, $currency);
    }

    /**
     * Get the tax percentage
     *
     * @param OrderItemInterface $item
     * @return float
     */
    public function getTaxRate(OrderItemInterface $item): float
    {
        if (($item->getTaxAmount() > 0)) {
            return (float)$item->getTaxPercent();
        }

        return 0.0;
    }

    /**
     * Get the merchant item ID
     *
     * @param OrderItemInterface $item
     * @return string
     */
    public function getMerchantItemId(OrderItemInterface $item): string
    {
        return $item->getSku() . '_' . $item->getQuoteItemId();
    }

    /**
     * Get the item name, if a discount is applied, add the rule name to the item name
     *
     * @param OrderItemInterface $item
     * @param string $discountDescription
     * @return string
     * @throws Exception
     */
    private function getName(OrderItemInterface $item, string $discountDescription): string
    {
        if (!$this->config->canAddCouponToItemNames($item->getStoreId())) {
            return $item->getName();
        }

        if ($discountDescription) {
            return $item->getName() . __(' - (Discount applied: ') . $discountDescription . __(')');
        }

        return $item->getName();
    }

    /**
     * Get the item description
     *
     * @param OrderItemInterface $item
     * @return string
     */
    private function getDescription(OrderItemInterface $item): string
    {
        if (!$item->getAppliedRuleIds()) {
            return $item->getDescription() ?? '';
        }

        $itemDescription = $item->getDescription() ?? '';

        if ($itemDescription) {
            return $itemDescription . ' - (Discount amount: ' . $item->getDiscountAmount() . __(')');
        }

        return 'Discount amount: ' . $item->getDiscountAmount();
    }

    /**
     * @param Item $item
     * @return bool
     */
    private function canAddToShoppingCart(Item $item): bool
    {
        $product = $item->getProduct();

        if (!$product) {
            return false;
        }

        // Bundled products with price type dynamic should not be added, we want the simple products instead
        if ($item->getProductType() === Type::TYPE_BUNDLE
            && (int)$product->getPriceType() === Price::PRICE_TYPE_DYNAMIC
        ) {
            return false;
        }

        // Products with no parent can be added
        /** @var Item $parentItem */
        $parentItem = $item->getParentItem();
        if ($parentItem === null) {
            return true;
        }

        $parentItemProductType = $parentItem->getProductType();

        // We do not want to add the item if the parent item is not a bundle
        if ($parentItemProductType !== Type::TYPE_BUNDLE) {
            return false;
        }

        // Do not add the item if the parent is a fixed price bundle product, the bundle product is added instead
        if (($parentItem->getProduct() !== null)
            && (int)$parentItem->getProduct()->getPriceType() === Price::PRICE_TYPE_FIXED
        ) {
            return false;
        }

        return true;
    }
}
