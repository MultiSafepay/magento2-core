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
use Magento\Tax\Model\Config;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\PriceUtil;
use Magento\Sales\Api\Data\OrderItemInterface;
use MultiSafepay\ConnectCore\Config\Config as MultiSafepayConfig;

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
     * @magentoConfigFixture default_store multisafepay/general/use_base_currency 0
     * @throws LocalizedException
     */
    public function testGetGrandTotal(): void
    {
        $order = $this->getOrder();

        self::assertEquals($this->priceUtil->getGrandTotal($order), (float)$order->getGrandTotal());
    }

    /**
     * @magentoDbIsolation     enabled
     * @magentoAppIsolation    enabled
     */
    public function testGetBaseUnitPriceWithCatalogPriceIncludeTaxSetting(): void
    {
        $this->checkCalculationEquals(1878.89, [
            'config_data' => [
                'config_overrides' => [
                    Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX => 1,
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
        $this->checkCalculationEquals(1942.37, [
            'config_data' => [
                'config_overrides' => [
                    Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX => 0,
                    sprintf(
                        MultiSafepayConfig::DEFAULT_PATH_PATTERN,
                        MultiSafepayConfig::USE_BASE_CURRENCY
                    ) => 1,
                ],
            ],
        ]);
    }

    /**
     * @param float $expectedUnitPrice
     * @param array $additionalTaxConfigs
     */
    private function checkCalculationEquals(float $expectedUnitPrice, array $additionalTaxConfigs = []): void
    {
        $quote = $this->getQuoteWithTaxesAndDiscount($additionalTaxConfigs);
        $quoteItem = $quote->getAllItems()[0];
        $orderItem = $this->getObjectManager()
            ->create(OrderItemInterface::class)
            ->setData($quoteItem->getData())
            ->setQtyOrdered($quoteItem->getQty());
        $unitPrice = round($this->priceUtil->getUnitPrice($orderItem, $quote->getStoreId()), 2);

        self::assertEquals($unitPrice, $expectedUnitPrice);

        $grandTotal = $unitPrice * $quote->getItemsQty() + $quoteItem->getBaseTaxAmount() +
                      $quote->getBaseShippingAmount();

        self::assertEquals(round($grandTotal, 2), (float)$quote->getBaseGrandTotal());
    }
}
