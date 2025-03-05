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

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Magento\Store\Model\StoreManagerInterface;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\EinvoicingConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\IdealConfigProvider;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Util\InvoiceUrlUtil;

class InvoiceUrlUtilTest extends AbstractTestCase
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var GatewayConfig
     */
    private $gatewayConfig;

    /**
     * @var InvoiceUrlUtil
     */
    private $invoiceUrlUtil;

    /**
     * @var Order
     */
    private $order;

    /**
     * @var Invoice
     */
    private $invoice;

    /**
     * @var Payment
     */
    private $payment;

    /**
     * @var Store
     */
    private $store;

    protected function setUp(): void
    {
        $this->storeManager = $this->getObjectManager()->create(StoreManager::class);
        $this->gatewayConfig = $this->getObjectManager()->create(GatewayConfig::class);
        $this->order = $this->getObjectManager()->create(Order::class);
        $this->invoice = $this->getObjectManager()->create(Invoice::class);
        $this->payment = $this->getObjectManager()->create(Payment::class);
        $this->store = $this->getObjectManager()->create(Store::class);

        $this->invoiceUrlUtil = new InvoiceUrlUtil($this->storeManager, $this->gatewayConfig);
    }

    /**
     * @throws NoSuchEntityException
     */
    public function testReturnsEmptyStringIfPaymentMethodIsNotEinvoicing()
    {
        $this->payment->setMethod(IdealConfigProvider::CODE);
        $this->order->setPayment($this->payment);

        $this->assertEquals('', $this->invoiceUrlUtil->getInvoiceUrl($this->order, $this->invoice));
    }

    /**
     * @throws NoSuchEntityException
     */
    public function testReturnsEmptyStringIfCustomerIsGuest()
    {
        $this->payment->setMethod(EinvoicingConfigProvider::CODE);
        $this->order->setPayment($this->payment);

        $this->assertEquals('', $this->invoiceUrlUtil->getInvoiceUrl($this->order, $this->invoice));
    }

    /**
     * @magentoConfigFixture default_store payment/multisafepay_einvoicing/use_custom_invoice_url 1
     * @magentoConfigFixture default_store web/secure/base_url https://example.com
     * @magentoConfigFixture default_store web/unsecure/base_url http://example.com
     *
     * phpcs:ignore
     * @magentoConfigFixture default_store payment/multisafepay_einvoicing/custom_invoice_url {{store.secure_base_url}}invoice/{{invoice.increment_id}}
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function testReturnsCustomInvoiceUrlIfConfigured()
    {
        $this->setCustomOrderInfo();

        $expectedUrl = 'https://example.com/invoice/200000001';
        $this->assertEquals($expectedUrl, $this->invoiceUrlUtil->getInvoiceUrl($this->order, $this->invoice));
    }

    /**
     * @magentoConfigFixture default_store payment/multisafepay_einvoicing/use_custom_invoice_url 1
     * @magentoConfigFixture default_store web/secure/base_url https://example.com
     *
     * phpcs:ignore
     * @magentoConfigFixture default_store payment/multisafepay_einvoicing/custom_invoice_url https://foo.bar/invoice/{{invoice.increment_id}}
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function testReturnsCustomInvoiceUrlIfConfiguredWithExternalUrl()
    {
        $this->setCustomOrderInfo();

        $expectedUrl = 'https://foo.bar/invoice/200000001';
        $this->assertEquals($expectedUrl, $this->invoiceUrlUtil->getInvoiceUrl($this->order, $this->invoice));
    }

    /**
     * @magentoConfigFixture default_store payment/multisafepay_einvoicing/use_custom_invoice_url 0
     * @magentoConfigFixture default_store web/secure/base_url https://example.com
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function testReturnsDefaultInvoiceUrlIfCustomUrlNotConfigured()
    {
        $this->setCustomOrderInfo();

        $store = $this->createMock(Store::class);
        $store->method('getBaseUrl')->willReturn('https://example.com');

        $storeManager = $this->createMock(StoreManager::class);
        $storeManager->method('getStore')->willReturn($store);
        
        $invoiceUrlUtil = new InvoiceUrlUtil($storeManager, $this->gatewayConfig);

        $expectedUrl = 'https://example.com/sales/order/invoice/order_id/1';
        $this->assertEquals($expectedUrl, $invoiceUrlUtil->getInvoiceUrl($this->order, $this->invoice));
    }

    /**
     * @return void
     */
    private function setCustomOrderInfo()
    {
        $this->payment->setMethod(EinvoicingConfigProvider::CODE);
        $this->order->setPayment($this->payment);
        $this->order->setCustomerId(1);
        $this->order->setStoreId(1);
        $this->order->setIncrementId('100000001');
        $this->order->setEntityId(1);
        $this->invoice->setIncrementId('200000001');
        $this->invoice->setId(1);

        $this->storeManager->setCurrentStore($this->store->setId(1));
    }
}
