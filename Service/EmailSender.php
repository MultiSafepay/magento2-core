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

namespace MultiSafepay\ConnectCore\Service;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Container\InvoiceIdentity;
use Magento\Sales\Model\Order\Email\Container\OrderIdentity;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Invoice;
use MultiSafepay\ConnectCore\Config\Config;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\AfterpayConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\KlarnaConfigProvider;
use MultiSafepay\ConnectCore\Model\Ui\Gateway\PayafterConfigProvider;

class EmailSender
{
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
     * Email constructor.
     *
     * @param Config $config
     * @param InvoiceSender $invoiceSender
     * @param OrderIdentity $orderIdentity
     * @param OrderSender $orderSender
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Config $config,
        InvoiceSender $invoiceSender,
        OrderIdentity $orderIdentity,
        OrderSender $orderSender,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->config = $config;
        $this->invoiceSender = $invoiceSender;
        $this->orderIdentity = $orderIdentity;
        $this->orderSender = $orderSender;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param Order $order
     * @return bool
     */
    public function sendOrderConfirmationEmailAfterTransaction(Order $order): bool
    {
        if ($order->getEmailSent()) {
            return false;
        }

        if ($this->orderIdentity->isEnabled() && $this->config->getOrderConfirmationEmail() === 'after_transaction') {
            $this->orderSender->send($order);
            return true;
        }

        return false;
    }

    /**
     * @param Order $order
     * @return bool
     */
    public function sendOrderConfirmationEmailAfterPaidTransaction(Order $order): bool
    {
        if ($order->getEmailSent()) {
            return false;
        }

        $orderConfirmationEmail = $this->config->getOrderConfirmationEmail();

        if ($orderConfirmationEmail === 'after_paid_transaction' && $this->orderIdentity->isEnabled()) {
            $this->orderSender->send($order);
            return true;
        }

        return false;
    }

    /**
     * @param OrderPaymentInterface $payment
     * @param Invoice $invoice
     * @return bool
     * @throws Exception
     */
    public function sendInvoiceEmail(OrderPaymentInterface $payment, Invoice $invoice): bool
    {
        if ($invoice->getEmailSent()) {
            return false;
        }

        if ((bool)$this->scopeConfig->getValue(InvoiceIdentity::XML_PATH_EMAIL_ENABLED) === false) {
            return false;
        }

        $allowedMethods = [
            PayafterConfigProvider::CODE,
            KlarnaConfigProvider::CODE,
            AfterpayConfigProvider::CODE
        ];

        if (!in_array($payment->getMethod(), $allowedMethods, true)) {
            $this->invoiceSender->send($invoice);
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function checkOrderConfirmationBeforeTransaction(): bool
    {
        return $this->config->getOrderConfirmationEmail() === Config::BEFORE_TRANSACTION;
    }
}
