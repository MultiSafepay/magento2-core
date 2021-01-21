<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * Copyright © 2021 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder;

use Magento\Bundle\Model\Product\Price;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Item;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\ShoppingCartBuilder\CustomTotalBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\ShoppingCartBuilder\FoomanSurchargeTotalBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\ShoppingCartBuilder\OrderItemBuilder;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\ShoppingCartBuilder\ShippingItemBuilder;
use MultiSafepay\ConnectCore\Model\Api\Validator\CustomTotalValidator;
use MultiSafepay\ConnectCore\Util\CurrencyUtil;

class ShoppingCartBuilder
{

    /**
     * @var OrderItemBuilder
     */
    private $orderItemBuilder;

    /**
     * @var ShippingItemBuilder
     */
    private $shippingItemBuilder;

    /**
     * @var FoomanSurchargeTotalBuilder
     */
    private $foomanSurchargeBuilder;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var CustomTotalBuilder
     */
    private $customTotalBuilder;

    /**
     * @var CustomTotalValidator
     */
    private $customTotalValidator;

    /**
     * @var CurrencyUtil
     */
    private $currencyUtil;

    /**
     * ShoppingCartBuilder constructor.
     *
     * @param CustomTotalBuilder $customTotalBuilder
     * @param CustomTotalValidator $customTotalValidator
     * @param OrderItemBuilder $orderItemBuilder
     * @param FoomanSurchargeTotalBuilder $foomanSurchargeBuilder
     * @param ShippingItemBuilder $shippingItemBuilder
     * @param CartRepositoryInterface $quoteRepository
     * @param CurrencyUtil $currencyUtil
     */
    public function __construct(
        CustomTotalBuilder $customTotalBuilder,
        CustomTotalValidator $customTotalValidator,
        OrderItemBuilder $orderItemBuilder,
        FoomanSurchargeTotalBuilder $foomanSurchargeBuilder,
        ShippingItemBuilder $shippingItemBuilder,
        CartRepositoryInterface $quoteRepository,
        CurrencyUtil $currencyUtil
    ) {
        $this->customTotalBuilder = $customTotalBuilder;
        $this->customTotalValidator = $customTotalValidator;
        $this->orderItemBuilder = $orderItemBuilder;
        $this->foomanSurchargeBuilder = $foomanSurchargeBuilder;
        $this->shippingItemBuilder = $shippingItemBuilder;
        $this->quoteRepository = $quoteRepository;
        $this->currencyUtil = $currencyUtil;
    }

    /**
     * @param OrderInterface $order
     * @return ShoppingCart
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function build(OrderInterface $order): ShoppingCart
    {
        $storeId = $order->getStoreId();

        $items = [];
        $orderItems = $order->getItems();

        $currency = $this->currencyUtil->getCurrencyCode($order);

        /** @var Item $item */
        foreach ($orderItems as $item) {
            if (!$this->canAddtoShoppingCart($item)) {
                continue;
            }
            $items[] = $this->orderItemBuilder->build($item, $currency, $storeId);
        }

        if ($order->getShippingAmount() > 0) {
            $items[] = $this->shippingItemBuilder->build($order, $currency);
        }

        try {
            $quote = $this->quoteRepository->get($order->getQuoteId());
        } catch (NoSuchEntityException $e) {
            return new ShoppingCart($items);
        }

        $items = array_merge($items, $this->getItemsFromQuote($quote, $currency));
        $items = array_merge($items, $this->getItemsFromFoomanTotalGroup($quote, $currency));

        return new ShoppingCart($items);
    }

    /**
     * @param CartInterface $quote
     * @param string $currency
     * @return array
     */
    private function getItemsFromQuote(CartInterface $quote, string $currency): array
    {
        $items = [];
        foreach ($quote->getTotals() as $total) {
            if (!$this->customTotalValidator->validate($total)) {
                continue;
            }

            if (!in_array($total->getCode(), CustomTotalBuilder::DEFAULT_TOTALS, true)) {
                $items[] = $this->customTotalBuilder->build($total, $currency);
            }
        }

        return $items;
    }

    /**
     * @param CartInterface $quote
     * @param string $currency
     * @return array
     */
    private function getItemsFromFoomanTotalGroup(CartInterface $quote, string $currency): array
    {
        $storeId = $quote->getStoreId();

        $shippingAddress = $quote->getShippingAddress();
        $extensionAttributes = $shippingAddress->getExtensionAttributes();

        if (!is_object($extensionAttributes)) {
            return [];
        }

        if (!method_exists($extensionAttributes, 'getFoomanTotalGroup')) {
            return [];
        }

        $foomanTotals = $extensionAttributes->getFoomanTotalGroup();
        if ($foomanTotals === null) {
            return [];
        }

        $items = [];
        foreach ($foomanTotals->getItems() as $total) {
            $items[] = $this->foomanSurchargeBuilder->build($total, $currency, $storeId);
        }

        return $items;
    }

    /**
     * @param Item $item
     * @return bool
     */
    public function canAddtoShoppingCart(Item $item): bool
    {
        $productType = $item->getProductType();
        $product = $item->getProduct();
        $parentItem = $item->getParentItem();

        if ($product === null) {
            return false;
        }

        // Bundled products with price type dynamic should not be added, we want the simple products instead
        if ($productType === Type::TYPE_BUNDLE
            && (int) $product->getPriceType() === Price::PRICE_TYPE_DYNAMIC) {
            return false;
        }

        // Products with no parent can be added
        if ($parentItem === null) {
            return true;
        }

        $parentItemProductType = $parentItem->getProductType();

        // We do not want to add the item if the parent item is not a bundle
        if ($parentItemProductType !== Type::TYPE_BUNDLE) {
            return false;
        }

        // Do not add the item if the parent is a fixed price bundle product, the bundle product is added instead
        if ($parentItemProductType === Type::TYPE_BUNDLE
            && ($parentItem->getProduct() !== null)
            && (int) $parentItem->getProduct()->getPriceType() === Price::PRICE_TYPE_FIXED) {
            return false;
        }

        return true;
    }
}
