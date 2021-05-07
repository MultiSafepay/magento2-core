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

use Magento\Config\Model\ResourceModel\Config as ConfigResourceModel;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use Magento\Framework\ObjectManagerInterface;
use MultiSafepay\ConnectCore\Util\PriceUtil;

class PriceUtilTest extends AbstractTestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var PriceUtil
     */
    private $priceUtil;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->priceUtil = $this->objectManager->create(PriceUtil::class);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/use_base_currency 0
     * @throws LocalizedException
     */
    public function testGetGrandTotal(): void
    {
        $order = $this->getOrder();

        self::assertEquals($this->priceUtil->getGrandTotal($order), (float)$order->getGrandTotal());
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_taxable_product_and_customer.php
     * @magentoConfigFixture default_store multisafepay/general/use_base_currency 0
     * @magentoConfigFixture default_store tax/calculation/price_includes_tax 1
     */
    public function testGetUnitPriceWithCatalogPriceIncludeTaxSetting(): void
    {
        $quote = $this->getQuote('', true);

        //foreach ($order->getItems() as $item) {
        //    $unitPrice = $this->priceUtil->getUnitPrice($item);
        //}

        ////Add is_active_payment_token_enabler to additionalInformation
        //$payment->setAdditionalInformation('is_active_payment_token_enabler', $type);
        //$vaultUtil = $this->getVaulUtiltObject();
        //
        //self::assertTrue($vaultUtil->validateVaultTokenEnabler($payment->getAdditionalInformation()));

        return;
    }
}
