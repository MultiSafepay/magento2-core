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

namespace MultiSafepay\ConnectCore\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public const DEFAULT_PATH_PATTERN = 'multisafepay/general/%s';
    public const ADVANCED_PATH_PATTERN = 'multisafepay/advanced/%s';

    public const TEST_API_KEY = 'test_api_key';
    public const LIVE_API_KEY = 'live_api_key';
    public const API_MODE = 'mode';
    public const DEBUG = 'debug';
    public const ORDER_CONFIRMATION_EMAIL = 'order_confirmation_email';
    public const REFUND_DESCRIPTION = 'refund_custom_description';
    public const USE_BASE_CURRENCY = 'use_base_currency';
    public const PRESELECTED_METHOD = 'preselected_method';
    public const CUSTOM_TOTALS = 'custom_totals';
    public const PENDING_STATUS = 'order_status';
    public const PENDING_PAYMENT_STATUS = 'pending_payment_order_status';
    public const PROCESSING_ORDER_STATUS = 'processing_order_status';
    public const CREATE_INVOICE_AUTOMATICALLY = 'create_invoice';
    public const BEFORE_TRANSACTION = 'before_transaction';

    public const ADVANCED_DISABLE_SHOPPING_CART = 'disable_shopping_cart';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param string $field
     * @param null $storeId
     * @return mixed
     */
    public function getValue(string $field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            sprintf(self::DEFAULT_PATH_PATTERN, $field),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param string $path
     * @param null $storeId
     * @return mixed
     */
    public function getValueByPath(string $path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param string $field
     * @param null $storeId
     * @return mixed
     */
    public function getAdvancedValue(string $field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            sprintf(self::ADVANCED_PATH_PATTERN, $field),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isLiveMode($storeId = null): bool
    {
        return ((int)$this->getValue(self::API_MODE, $storeId) === 1);
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function getApiKey($storeId = null): string
    {
        if (!$this->isLiveMode($storeId)) {
            return (string)$this->getValue(self::TEST_API_KEY, $storeId);
        }

        return (string)$this->getValue(self::LIVE_API_KEY, $storeId);
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isDebug($storeId = null): bool
    {
        return (bool)$this->getValue(self::DEBUG, $storeId);
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function getOrderConfirmationEmail($storeId = null): string
    {
        return (string)$this->getValue(self::ORDER_CONFIRMATION_EMAIL, $storeId);
    }

    /**
     * @param $orderId
     * @param null $storeId
     * @return string
     */
    public function getRefundDescription($orderId, $storeId = null): string
    {
        $refundDescription = (string)$this->getValue(self::REFUND_DESCRIPTION, $storeId);

        if (empty($refundDescription)) {
            return ('Refund for order #' . $orderId);
        }

        return str_replace('{{order.increment_id}}', $orderId, $refundDescription);
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function useBaseCurrency($storeId = null): bool
    {
        return (bool)$this->getValue(self::USE_BASE_CURRENCY, $storeId);
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function getPreselectedMethod($storeId = null): string
    {
        return (string)$this->getValue(self::PRESELECTED_METHOD, $storeId);
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function getCustomTotals($storeId = null): string
    {
        return (string)$this->getAdvancedValue(self::CUSTOM_TOTALS, $storeId);
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function getProcessingStatus($storeId = null): string
    {
        return (string)$this->getValue(self::PROCESSING_ORDER_STATUS, $storeId);
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function getPendingPaymentStatus($storeId = null): string
    {
        return (string)$this->getValue(self::PENDING_PAYMENT_STATUS, $storeId);
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function getPendingStatus($storeId = null): string
    {
        return (string)$this->getValue(self::PENDING_STATUS, $storeId);
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isCreateOrderInvoiceAutomatically($storeId = null): bool
    {
        return (bool)$this->getValue(self::CREATE_INVOICE_AUTOMATICALLY, $storeId);
    }
}
