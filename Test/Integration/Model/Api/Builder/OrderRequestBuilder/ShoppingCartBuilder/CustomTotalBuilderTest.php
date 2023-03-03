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

namespace MultiSafepay\ConnectCore\Test\Integration\Model\Api\Builder\OrderRequestBuilder\ShoppingCartBuilder;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use MultiSafepay\ConnectCore\Model\Api\Builder\OrderRequestBuilder\ShoppingCartBuilder\CustomTotalBuilder;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\CurrencyUtil;

class CustomTotalBuilderTest extends AbstractTestCase
{
    /**
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     *
     * @throws LocalizedException
     */
    public function testCustomTotalWithEmptyQuote(): void
    {
        $order = $this->getOrder();
        $currency = $this->getCurrencyUtil()->getCurrencyCode($order);

        $totals = $this->getCustomTotalBuilder()->build($order, $currency);

        self::assertSame([], $totals);
    }

    /**
     * @magentoDataFixture   Magento/Sales/_files/quote.php
     * @magentoDataFixture   Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store multisafepay/general/mode 0
     * @magentoConfigFixture default_store multisafepay/general/test_api_key testkey
     *
     * @throws LocalizedException
     * @throws Exception
     */
    public function testCustomTotalWithQuoteButNoCustomTotal(): void
    {
        $quote = $this->getQuote('test01');
        $order = $this->getOrder();
        $order->setQuoteId($quote->getId());

        $currency = $this->getCurrencyUtil()->getCurrencyCode($order);

        $totals = $this->getCustomTotalBuilder()->build($order, $currency);

        self::assertSame([], $totals);
    }

    /**
     * @return CustomTotalBuilder
     */
    private function getCustomTotalBuilder(): CustomTotalBuilder
    {
        return $this->getObjectManager()->create(CustomTotalBuilder::class);
    }

    /**
     * @return CurrencyUtil
     */
    private function getCurrencyUtil(): CurrencyUtil
    {
        return $this->getObjectManager()->create(CurrencyUtil::class);
    }
}
