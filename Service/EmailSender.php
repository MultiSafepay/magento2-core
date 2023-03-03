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

namespace MultiSafepay\ConnectCore\Service;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\MailException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Email\Container\InvoiceIdentity;
use Magento\Sales\Model\Order\Email\Container\OrderIdentity;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Invoice;
use MultiSafepay\ConnectCore\Config\Config;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\AfterpayConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\KlarnaConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\PayafterConfigProvider;
use MultiSafepay\ConnectCore\Util\LegacyUtil;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailSender
{
    public const AFTER_TRANSACTION_EMAIL_TYPE = 'after_transaction';
    public const AFTER_PAID_TRANSACTION_EMAIL_TYPE = 'after_paid_transaction';

    /**
     * @var OrderSender
     */
    private $orderSender;

    /**
     * @var OrderIdentity
     */
    private $orderIdentity;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var InvoiceSender
     */
    private $invoiceSender;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var GatewayConfig
     */
    private $gatewayConfig;

    /**
     * Email constructor.
     *
     * @param Config $config
     * @param InvoiceSender $invoiceSender
     * @param OrderIdentity $orderIdentity
     * @param OrderSender $orderSender
     * @param ScopeConfigInterface $scopeConfig
     * @param gatewayConfig $gatewayConfig
     */
    public function __construct(
        Config $config,
        InvoiceSender $invoiceSender,
        OrderIdentity $orderIdentity,
        OrderSender $orderSender,
        ScopeConfigInterface $scopeConfig,
        GatewayConfig $gatewayConfig
    ) {
        $this->config = $config;
        $this->invoiceSender = $invoiceSender;
        $this->orderIdentity = $orderIdentity;
        $this->orderSender = $orderSender;
        $this->scopeConfig = $scopeConfig;
        $this->gatewayConfig = $gatewayConfig;
    }

    /**
     * Send the order confirmation email
     *
     * @param OrderInterface $order
     * @param string $emailType
     * @return bool
     * @throws Exception
     */
    public function sendOrderConfirmationEmail(
        OrderInterface $order,
        string $emailType = self::AFTER_TRANSACTION_EMAIL_TYPE
    ): bool {
        if ($order->getEmailSent() || !$this->orderIdentity->isEnabled()) {
            return false;
        }

        if ($order->getPayment()) {
            $this->gatewayConfig->setMethodCode($order->getPayment()->getMethod());
        }

        $emailTypeConfig = $this->config->getOrderConfirmationEmail();

        if ($this->gatewayConfig->getValue(Config::OVERRIDE_ORDER_CONFIRMATION_EMAIL)) {
            $emailTypeConfig = $this->gatewayConfig->getValue(Config::ORDER_CONFIRMATION_EMAIL) ?? '';
        }

        if ($emailType === $emailTypeConfig) {
            $this->orderSender->send($order);

            return true;
        }

        return false;
    }

    /**
     * @param OrderPaymentInterface $payment
     * @param Invoice $invoice
     * @return bool
     * @throws MailException
     * @throws Exception
     */
    public function sendInvoiceEmail(OrderPaymentInterface $payment, Invoice $invoice): bool
    {
        if ((bool)$this->scopeConfig->getValue(InvoiceIdentity::XML_PATH_EMAIL_ENABLED) === false) {
            throw new MailException(__('Sending invoice emails disabled'));
        }

        $disallowedMethods = [
            PayafterConfigProvider::CODE,
            KlarnaConfigProvider::CODE,
            AfterpayConfigProvider::CODE,
            LegacyUtil::LEGACY_AFTERPAY_CODE,
            LegacyUtil::LEGACY_KLARNA_CODE,
            LegacyUtil::LEGACY_PAYAFTER_CODE,
        ];

        if (!$invoice->getEmailSent() && !in_array($payment->getMethod(), $disallowedMethods, true)) {
            $this->invoiceSender->send($invoice);

            return true;
        }

        return false;
    }

    /**
     * Check if order confirmation e-mail needs to be sent before transaction
     *
     * @param string $methodCode
     * @return bool
     */
    public function checkOrderConfirmationBeforeTransaction(string $methodCode): bool
    {
        $this->gatewayConfig->setMethodCode($methodCode);

        if (!$this->gatewayConfig->getValue(Config::OVERRIDE_ORDER_CONFIRMATION_EMAIL)) {
            return $this->config->getOrderConfirmationEmail() === Config::BEFORE_TRANSACTION;
        }

        if ($gatewaySpecificSetting = $this->gatewayConfig->getValue(Config::ORDER_CONFIRMATION_EMAIL)) {
            return $gatewaySpecificSetting === Config::BEFORE_TRANSACTION;
        }

        return $this->config->getOrderConfirmationEmail() === Config::BEFORE_TRANSACTION;
    }
}
