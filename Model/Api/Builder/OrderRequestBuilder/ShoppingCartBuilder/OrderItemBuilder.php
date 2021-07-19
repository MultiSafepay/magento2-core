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

namespace MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\ShoppingCartBuilder;

use Magento\Bundle\Model\Product\Price;
use Magento\Catalog\Model\Product\Type;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart\Item as TransactionItem;
use MultiSafepay\ConnectCore\Util\JsonHandler;
use MultiSafepay\ConnectCore\Util\PriceUtil;
use MultiSafepay\ConnectCore\Util\TaxUtil;
use MultiSafepay\ValueObject\Money;

class OrderItemBuilder implements ShoppingCartBuilderInterface
{
    /**
     * @var PriceUtil
     */
    private $priceUtil;

    /**
     * @var JsonHandler
     */
    private $jsonHandler;

    /**
     * @var TaxUtil
     */
    private $taxUtil;

    /**
     * OrderItemBuilder constructor.
     *
     * @param PriceUtil $priceUtil
     * @param JsonHandler $jsonHandler
     * @param TaxUtil $taxUtil
     */
    public function __construct(
        PriceUtil $priceUtil,
        JsonHandler $jsonHandler,
        TaxUtil $taxUtil
    ) {
        $this->priceUtil = $priceUtil;
        $this->jsonHandler = $jsonHandler;
        $this->taxUtil = $taxUtil;
    }

    /**
     * @param OrderInterface $order
     * @param string $currency
     * @return array
     */
    public function build(OrderInterface $order, string $currency): array
    {
        $storeId = $order->getStoreId();
        $items = [];
        $orderItems = $order->getItems();
        $weeTaxIdentifier = 0;

        foreach ($orderItems as $item) {
            if (!$this->canAddToShoppingCart($item)) {
                continue;
            }

            $unitPrice = $this->priceUtil->getUnitPrice($item, $storeId);
            $items[] = (new TransactionItem())
                ->addName($item->getName())
                ->addUnitPrice(new Money(round($unitPrice * 100, 10), $currency))
                ->addQuantity((float)$item->getQtyOrdered())
                ->addDescription($item->getDescription() ?? '')
                ->addMerchantItemId($item->getSku())
                ->addTaxRate((float)$item->getTaxPercent());

            if (!empty($weeeTaxData = $this->jsonHandler->readJSON($item->getWeeeTaxApplied()))) {
                foreach ($weeeTaxData as $weeTax) {
                    $weeTaxUnitPrice = $this->priceUtil->getWeeeTaxUnitPrice($weeTax, $storeId);
                    $items[] = (new TransactionItem())
                        ->addName($weeTax['title'])
                        ->addUnitPrice(new Money(round($weeTaxUnitPrice * 100, 10), $currency))
                        ->addQuantity((float)$item->getQtyOrdered())
                        ->addDescription($weeTax['title'] ?? '')
                        ->addMerchantItemId($weeTax['title'] . ($weeTaxIdentifier++))
                        ->addTaxRate($this->taxUtil->applyWeeTaxRate($item, $storeId));
                }
            }
        }

        return $items;
    }

    /**
     * @param OrderItemInterface $item
     * @return bool
     */
    private function canAddToShoppingCart(OrderItemInterface $item): bool
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
