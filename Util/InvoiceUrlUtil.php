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

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Store\Model\StoreManagerInterface;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\EinvoicingConfigProvider;

class InvoiceUrlUtil
{
    public const DEFAULT_INVOICE_URL = '/sales/order/invoice/order_id/';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var GatewayConfig
     */
    private $gatewayConfig;

    /**
     * InvoiceUtil constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param GatewayConfig $gatewayConfig
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        GatewayConfig $gatewayConfig
    ) {
        $this->storeManager = $storeManager;
        $this->gatewayConfig = $gatewayConfig;
    }

    /**
     * @param Order $order
     * @param Invoice $invoice
     * @return string
     * @throws NoSuchEntityException
     */
    public function getInvoiceUrl(Order $order, Invoice $invoice): string
    {
        // Check if the payment method is E-invoicing, if not, then we don't need to add the URL
        if ($order->getPayment()->getMethod() !== EinvoicingConfigProvider::CODE) {
            return '';
        }

        // Check if the customer was a guest. Guest customers are not able to view the invoice, so we don't add the URL
        if (!$order->getCustomerId()) {
            return '';
        }

        if ($order->getPayment()) {
            $this->gatewayConfig->setMethodCode($order->getPayment()->getMethod());
        }

        if ($this->gatewayConfig->getValue(Config::USE_CUSTOM_INVOICE_URL)) {
            $customUrl = $this->gatewayConfig->getValue(Config::CUSTOM_INVOICE_URL);

            if ($customUrl) {
                $store = $this->storeManager->getStore($order->getStoreId());

                $baseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_WEB);
                $secureBaseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_WEB, true);

                $availableCustomVariables = [
                    '{{invoice.increment_id}}' => $invoice->getIncrementId(),
                    '{{invoice.invoice_id}}' => (string)$invoice->getId(),
                    '{{order.increment_id}}' => $order->getIncrementId(),
                    '{{order.order_id}}' => (string)$order->getEntityId(),
                    '{{store.unsecure_base_url}}' => $baseUrl,
                    '{{store.secure_base_url}}' => $secureBaseUrl,
                    '{{store.code}}' => $store->getCode(),
                    '{{store.store_id}}' => (string)$store->getId(),
                ];

                foreach ($availableCustomVariables as $var => $value) {
                    $customUrl = str_replace($var, $value, $customUrl);
                }
            }

            return $this->formatUrl($customUrl);
        }

        $url = $this->storeManager->getStore($order->getStoreId())
                ->getBaseUrl() . self::DEFAULT_INVOICE_URL . $order->getId();

        return $this->formatUrl($url);
    }

    /**
     * Removes double slashes from the URL, excludes the first occurrence since it's the protocol (http:// or https://)
     *
     * @param string $url
     * @return string
     */
    private function formatUrl(string $url): string
    {
        $match = strpos($url, '//');
        if ($match !== false) {
            $url = substr($url, 0, $match + 1) . str_replace('//', '/', substr($url, $match + 1));
        }

        return $url;
    }
}
