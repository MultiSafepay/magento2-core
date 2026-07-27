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

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Tax\Model\Config as MagentoConfig;
use Magento\Tax\Model\Sales\Order\Tax;
use MultiSafepay\ConnectCore\Config\Config;

class PriceUtil
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var TaxUtil
     */
    private $taxUtil;

    /**
     * PriceUtil constructor.
     *
     * @param Config $config
     * @param ScopeConfigInterface $scopeConfig
     * @param TaxUtil $taxUtil
     */
    public function __construct(
        Config $config,
        ScopeConfigInterface $scopeConfig,
        TaxUtil $taxUtil
    ) {
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
        $this->taxUtil = $taxUtil;
    }

    /**
     * @param OrderInterface $order
     * @return float
     */
    public function getGrandTotal(OrderInterface $order): float
    {
        if ($this->config->useBaseCurrency($order->getStoreId())) {
            return (float)$order->getBaseGrandTotal();
        }

        return (float)$order->getGrandTotal();
    }

    /**
     * @param OrderItemInterface $item
     * @param $storeId
     * @return float
     */
    public function getUnitPrice(OrderItemInterface $item, $storeId): float
    {
        $orderedQuantity = (float)$item->getQtyOrdered();

        if ($orderedQuantity <= 0) {
            return 0.0;
        }

        return $this->shouldUseInclTaxAmounts($storeId)
            ? $this->getUnitPriceInclTax($item, $storeId, $orderedQuantity)
            : $this->getUnitPriceExclTax($item, $storeId, $orderedQuantity);
    }

    /**
     * Returns the unit price excluding tax for the given order item, store ID, and ordered quantity.
     *
     * @param OrderItemInterface $item
     * @param $storeId
     * @param float $orderedQuantity
     * @return float
     */
    public function getUnitPriceExclTax(OrderItemInterface $item, $storeId, float $orderedQuantity): float
    {
        if ($orderedQuantity <= 0) {
            return 0.0;
        }

        if ($this->config->useBaseCurrency($storeId)) {
            return ($item->getBaseRowTotal() - $item->getBaseDiscountAmount()
                    + $item->getBaseDiscountTaxCompensationAmount()) / $orderedQuantity;
        }

        return ($item->getRowTotal() - $item->getDiscountAmount()
                + $item->getDiscountTaxCompensationAmount()) / $orderedQuantity;
    }

    /**
     * Returns the incl-tax unit price derived from the row total so that
     *
     * @param OrderItemInterface $item
     * @param $storeId
     * @param float $orderedQuantity
     * @return float
     */
    public function getUnitPriceInclTax(OrderItemInterface $item, $storeId, float $orderedQuantity): float
    {
        if ($orderedQuantity <= 0) {
            return 0.0;
        }

        $addedTaxPercentage = 1 + ($item->getTaxPercent() / 100);
        $discountScaleFactor = $this->catalogPriceIncludesTax($storeId) ? 1.0 : $addedTaxPercentage;

        if ($this->config->useBaseCurrency($storeId)) {
            return (($item->getBaseRowTotalInclTax() - $item->getBaseDiscountAmount() * $discountScaleFactor) /
                    $orderedQuantity / $addedTaxPercentage);
        }

        return (($item->getRowTotalInclTax() - $item->getDiscountAmount() * $discountScaleFactor) /
                $orderedQuantity / $addedTaxPercentage);
    }

    /**
     * Returns true when the catalog price configuration is set to "Including Tax"
     * (tax/calculation/price_includes_tax = 1).
     *
     * @param $storeId
     * @return bool
     */
    private function catalogPriceIncludesTax($storeId): bool
    {
        return (bool)$this->scopeConfig->getValue(
            MagentoConfig::CONFIG_XML_PATH_PRICE_INCLUDES_TAX,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param OrderItemInterface $item
     * @param $storeId
     * @return float
     */
    public function getUnitRowItemPriceWithTax(OrderItemInterface $item, $storeId): float
    {
        $orderedQuantity = (float)$item->getQtyOrdered();

        if ($orderedQuantity <= 0) {
            return 0.0;
        }

        if ($this->config->useBaseCurrency($storeId)) {
            return $this->getUnitPrice($item, $storeId) + ($item->getBaseTaxAmount() / $orderedQuantity);
        }

        return $this->getUnitPrice($item, $storeId) + ($item->getTaxAmount() / $orderedQuantity);
    }

    /**
     * Returns the shipping unit price (excl tax) derived from the order's shipping amounts so that
     *
     * @param OrderInterface $order
     * @return float
     * @throws NoSuchEntityException
     */
    public function getShippingUnitPrice(OrderInterface $order): float
    {
        $shippingTaxRate = 1 + ($this->taxUtil->getShippingTaxRate($order) / 100);
        $discountScaleFactor = $this->discountAppliedOnInclTaxPrices($order->getStoreId())
            ? 1.0
            : $shippingTaxRate;

        if ($this->config->useBaseCurrency($order->getStoreId())) {
            if ($order->getBaseShippingInclTax() === $order->getBaseShippingAmount()) {
                return $order->getBaseShippingAmount() - $order->getBaseShippingDiscountAmount();
            }

            return ($order->getBaseShippingInclTax() - ($order->getBaseShippingDiscountAmount() * $discountScaleFactor))
                   / $shippingTaxRate;
        }

        if ($order->getShippingInclTax() === $order->getShippingAmount()) {
            return $order->getShippingAmount() - $order->getShippingDiscountAmount();
        }

        return ($order->getShippingInclTax() - ($order->getShippingDiscountAmount() * $discountScaleFactor))
               / $shippingTaxRate;
    }

    /**
     * Returns true when the discount is applied on prices including tax (tax/calculation/discount_tax = 1)
     *
     * @param $storeId
     * @return bool
     */
    private function discountAppliedOnInclTaxPrices($storeId): bool
    {
        return (bool)$this->scopeConfig->getValue(
            MagentoConfig::CONFIG_XML_PATH_DISCOUNT_TAX,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param array $weeeTaxData
     * @param $storeId
     * @return float
     */
    public function getWeeeTaxUnitPrice(array $weeeTaxData, $storeId): float
    {
        if (!isset($weeeTaxData[0][Tax::KEY_BASE_AMOUNT], $weeeTaxData[0][Tax::KEY_AMOUNT])) {
            return 0.0;
        }

        return $this->config->useBaseCurrency($storeId) ? $weeeTaxData[0][Tax::KEY_BASE_AMOUNT] :
            $weeeTaxData[0][Tax::KEY_AMOUNT];
    }

    /**
     * Determines whether to use prices including tax based on the store configuration.
     *
     * @param $storeId
     * @return bool
     */
    private function shouldUseInclTaxAmounts($storeId): bool
    {
        $salesDisplayPrice = (int)$this->scopeConfig->getValue(
            MagentoConfig::XML_PATH_DISPLAY_SALES_PRICE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $displayPricesIncludingTax = [MagentoConfig::DISPLAY_TYPE_INCLUDING_TAX, MagentoConfig::DISPLAY_TYPE_BOTH];

        if (in_array($salesDisplayPrice, $displayPricesIncludingTax, true)) {
            return true;
        }

        return (bool)$this->scopeConfig->getValue(
            MagentoConfig::CONFIG_XML_PATH_PRICE_INCLUDES_TAX,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
