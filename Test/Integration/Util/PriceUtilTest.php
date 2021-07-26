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
 * Copyright © 2020 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 *
 */

declare(strict_types=1);

namespace MultiSafepay\Test\Integration\Util;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Pricing\PriceCurrencyInterface as PriceRounder;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Tax\Model\Config;
use MultiSafepay\ConnectCore\Config\Config as MultiSafepayConfig;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\PriceUtil;

class PriceUtilTest extends AbstractTestCase
{
    /**
     * @var PriceUtil
     */
    private $priceUtil;

    protected function setUp(): void
    {
        $this->priceUtil = $this->getObjectManager()->create(PriceUtil::class);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/use_base_currency 1
     * @throws LocalizedException
     */
    public function testGetGrandTotal(): void
    {
        $order = $this->getOrder();

        self::assertEquals((float)$order->getBaseGrandTotal(), $this->priceUtil->getGrandTotal($order));
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/order_with_shipping_and_invoice.php
     * @magentoConfigFixture default_store multisafepay/general/use_base_currency 1
     * @throws LocalizedException
     */
    public function testGetBaseShippingUnitPrice(): void
    {
        $order = $this->getOrder();

        self::assertEquals((float)$order->getBaseShippingAmount(), $this->priceUtil->getShippingUnitPrice($order));
    }

    /**
     * @magentoDbIsolation     enabled
     * @magentoAppIsolation    enabled
     */
    public function testGetBaseUnitPriceWithCatalogPriceIncludeTaxSetting(): void
    {
        $this->checkCalculationEquals([
            'config_data' => [
                'config_overrides' => [
                    Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX => 1,
                    Config::CONFIG_XML_PATH_DISCOUNT_TAX => 1,
                    Config::CONFIG_XML_PATH_APPLY_AFTER_DISCOUNT => 1,
                    sprintf(
                        MultiSafepayConfig::DEFAULT_PATH_PATTERN,
                        MultiSafepayConfig::USE_BASE_CURRENCY
                    ) => 1,
                ],
            ],
        ]);
    }

    /**
     * @magentoDbIsolation     enabled
     * @magentoAppIsolation    enabled
     */
    public function testGetBaseUnitPriceWithCatalogPriceExcludedTaxSetting(): void
    {
        $this->checkCalculationEquals([
            'config_data' => [
                'config_overrides' => [
                    Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX => 0,
                    Config::CONFIG_XML_PATH_DISCOUNT_TAX => 0,
                    Config::CONFIG_XML_PATH_APPLY_AFTER_DISCOUNT => 1,
                    sprintf(
                        MultiSafepayConfig::DEFAULT_PATH_PATTERN,
                        MultiSafepayConfig::USE_BASE_CURRENCY
                    ) => 1,
                ],
            ],
        ]);
    }

    /**
     * @param array $additionalTaxConfigs
     */
    private function checkCalculationEquals(array $additionalTaxConfigs = []): void
    {
        $objectManager = $this->getObjectManager();
        $priceRounder = $objectManager->create(PriceRounder::class);
        $quote = $this->getQuoteWithTaxesAndDiscount($additionalTaxConfigs);
        $quoteItem = $quote->getAllItems()[0];
        $orderItem = $objectManager->create(OrderItemInterface::class)->setData($quoteItem->getData())
            ->setQtyOrdered($quoteItem->getQty());
        $unitPrice = $this->priceUtil->getUnitPrice($orderItem, $quote->getStoreId());

        self::assertEquals(
            $priceRounder->roundPrice($quote->getBaseGrandTotal()),
            $priceRounder->roundPrice(
                $unitPrice * $quote->getItemsQty() + $quoteItem->getBaseTaxAmount() + $quote->getBaseShippingAmount()
            )
        );
    }
}
