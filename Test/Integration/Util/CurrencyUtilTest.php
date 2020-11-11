<?php declare(strict_types=1);

namespace MultiSafepay\Test\Integration\Util;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\CurrencyUtil;

class CurrencyUtilTest extends AbstractTestCase
{
    /**
     * @magentoConfigFixture default/currency/options/base FOOBAR2
     * @throws NoSuchEntityException
     */
    public function testGetBaseCurrencyCode()
    {
        $currencyUtil = $this->getCurrencyUtil();
        $this->assertEquals('FOOBAR', $currencyUtil->getBaseCurrencyCode('FOOBAR'));
        $this->assertEquals('FOOBAR2', $currencyUtil->getBaseCurrencyCode());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function testGetCurrencyCode()
    {
        $order = $this->getOrder();
        $order->setBaseCurrencyCode('FOOBAR');
        $currencyUtil = $this->getCurrencyUtil();
        $this->assertEquals('FOOBAR', $currencyUtil->getCurrencyCode($order));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function testGetOrderCurrencyCode()
    {
        $order = $this->getOrder();
        $order->setOrderCurrencyCode('FOOBAR');
        $currencyUtil = $this->getCurrencyUtil();
        $this->assertEquals('FOOBAR', $currencyUtil->getOrderCurrencyCode($order));
    }

    /**
     * @return CurrencyUtil
     */
    private function getCurrencyUtil(): CurrencyUtil
    {
        return $this->getObjectManager()->get(CurrencyUtil::class);
    }
}
