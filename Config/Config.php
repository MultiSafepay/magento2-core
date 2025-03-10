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

namespace MultiSafepay\ConnectCore\Config;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use MultiSafepay\ConnectCore\Util\EncryptorUtil;

class Config
{
    public const DEFAULT_PATH_PATTERN = 'multisafepay/general/%s';
    public const STATUS_PATH_PATTERN = 'multisafepay/status/%s';
    public const ADVANCED_PATH_PATTERN = 'multisafepay/advanced/%s';

    public const TEST_API_KEY = 'test_api_key';
    public const LIVE_API_KEY = 'live_api_key';
    public const API_MODE = 'mode';
    public const DEBUG = 'debug';
    public const OVERRIDE_ORDER_CONFIRMATION_EMAIL = 'override_order_confirmation_email';
    public const ORDER_CONFIRMATION_EMAIL = 'order_confirmation_email';
    public const TRANSACTION_DESCRIPTION = 'transaction_custom_description';
    public const REFUND_DESCRIPTION = 'refund_custom_description';
    public const USE_BASE_CURRENCY = 'use_base_currency';
    public const PRESELECTED_METHOD = 'preselected_method';
    public const CUSTOM_TOTALS = 'custom_totals';
    public const PENDING_STATUS = 'order_status';
    public const PENDING_PAYMENT_STATUS = 'pending_payment_order_status';
    public const PROCESSING_ORDER_STATUS = 'processing_order_status';
    public const CREATE_INVOICE_AUTOMATICALLY = 'create_invoice';
    public const BEFORE_TRANSACTION = 'before_transaction';
    public const USE_MANUAL_CAPTURE = 'use_manual_capture';
    public const MULTISAFEPAY_ACCOUNT_DATA = 'account_data';
    public const CHECKOUT_FIELDS = 'checkout_fields';
    public const PAYMENT_ICON = 'payment_icon';
    public const ICON_TYPE = 'icon_type';
    public const DISABLE_UTM_NOOVERRIDE = 'disable_utm_nooverride';
    public const SHOW_PAYMENT_PAGE = 'show_payment_page';
    public const USE_CUSTOM_INVOICE_URL = 'use_custom_invoice_url';
    public const CUSTOM_INVOICE_URL = 'custom_invoice_url';

    public const MULTISAFEPAY_INITIALIZED_STATUS = 'initialized_status';
    public const MULTISAFEPAY_UNCLEARED_STATUS = 'uncleared_status';
    public const MULTISAFEPAY_RESERVED_STATUS = 'reserved_status';
    public const MULTISAFEPAY_CHARGEBACK_STATUS = 'chargeback_status';
    public const MULTISAFEPAY_REFUNDED_STATUS = 'refunded_status';
    public const MULTISAFEPAY_PARTIAL_REFUNDED_STATUS = 'partial_refunded_status';

    public const ADVANCED_DISABLE_SHOPPING_CART = 'disable_shopping_cart';
    public const CANCEL_PAYMENTLINK = 'second_chance_paymentlink';
    public const PAYMENT_COMPONENT_TEMPLATE_ID = 'payment_component_template_id';
    public const ADD_COUPON_TO_ITEM_NAMES = 'add_coupon_to_item_names';

    public const USE_CUSTOMER_GROUP_COLLECTING_FLOWS = 'use_customer_group_collecting_flows';
    public const CUSTOMER_GROUP_COLLECTING_FLOWS = 'customer_group_collecting_flows';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var EncryptorUtil
     */
    protected $encryptorUtil;

    /**
     * Config constructor.
     *
     * @param EncryptorUtil $encryptorUtil
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        EncryptorUtil $encryptorUtil,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->encryptorUtil = $encryptorUtil;
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
    public function getStatusValue(string $field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            sprintf(self::STATUS_PATH_PATTERN, $field),
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
     * @throws Exception
     */
    public function getApiKey($storeId = null): string
    {
        return !$this->isLiveMode($storeId)
            ? $this->encryptorUtil->decrypt((string)$this->getValue(self::TEST_API_KEY, $storeId))
            : $this->encryptorUtil->decrypt((string)$this->getValue(self::LIVE_API_KEY, $storeId));
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
    public function createOrderInvoiceAutomatically($storeId = null): bool
    {
        return (bool)$this->getValue(self::CREATE_INVOICE_AUTOMATICALLY, $storeId);
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function getAccountData($storeId = null): string
    {
        return $this->getValue(self::MULTISAFEPAY_ACCOUNT_DATA, $storeId) ?? '';
    }

    /**
     * Retrieve the icon type
     *
     * @param $storeId
     * @return string
     */
    public function getIconType($storeId = null): string
    {
        return (string)$this->getValue(self::ICON_TYPE, $storeId);
    }

    /**
     * @param null $storeId
     * @return int
     */
    public function getCancelPaymentLinkOption($storeId = null): int
    {
        return (int)$this->getAdvancedValue(self::CANCEL_PAYMENTLINK, $storeId);
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function getInitializedStatus($storeId = null): string
    {
        return (string)$this->getStatusValue(self::MULTISAFEPAY_INITIALIZED_STATUS, $storeId);
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function getUnclearedStatus($storeId = null): string
    {
        return (string)$this->getStatusValue(self::MULTISAFEPAY_UNCLEARED_STATUS, $storeId);
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function getReservedStatus($storeId = null): string
    {
        return (string)$this->getStatusValue(self::MULTISAFEPAY_RESERVED_STATUS, $storeId);
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function getChargebackStatus($storeId = null): string
    {
        return (string)$this->getStatusValue(self::MULTISAFEPAY_CHARGEBACK_STATUS, $storeId);
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function getRefundedStatus($storeId = null): string
    {
        return (string)$this->getStatusValue(self::MULTISAFEPAY_REFUNDED_STATUS, $storeId);
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function getPartialRefundedStatus($storeId = null): string
    {
        return (string)$this->getStatusValue(self::MULTISAFEPAY_PARTIAL_REFUNDED_STATUS, $storeId);
    }

    /**
     * @param string $transactionStatus
     * @param null $storeId
     * @return string
     */
    public function getStatusByTransactionStatus(string $transactionStatus, $storeId = null): string
    {
        return (string)$this->getStatusValue($transactionStatus . '_status', $storeId);
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isUtmNoOverrideDisabled($storeId = null): bool
    {
        return (bool)$this->getAdvancedValue(self::DISABLE_UTM_NOOVERRIDE, $storeId);
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function getPaymentComponentTemplateId($storeId = null): string
    {
        return (string)$this->getAdvancedValue(self::PAYMENT_COMPONENT_TEMPLATE_ID, $storeId);
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function canAddCouponToItemNames($storeId = null): bool
    {
        return (bool)$this->getAdvancedValue(self::ADD_COUPON_TO_ITEM_NAMES, $storeId);
    }
}
