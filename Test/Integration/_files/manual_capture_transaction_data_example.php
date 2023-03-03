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

use MultiSafepay\Api\Transactions\CaptureRequest;
use MultiSafepay\Api\Transactions\Transaction as TransactionStatus;

return [
    'amount' => '10000',
    'amount_refunded' => '0',
    'currency' => 'USD',
    'customer' => [
        "firstname" => "firstname",
        "lastname" => "lastname",
        "company_name" => "",
        "address1" => "",
        "address2" => "",
        "house_number" => "",
        "zip_code" => "11111",
        "city" => "Los Angeles",
        "state" => "CA",
        "country" => "US",
        "phone" => "11111111",
        "email" => "customer@null.com",
        "locale" => "en_US",
    ],
    "description" => "Test manual capture payment for order #100000001",
    "financial_status" => TransactionStatus::INITIALIZED,
    "order_id" => "100000001",
    "transaction_id" => 123123123123123123,
    "order_total" => 100,
    "payment_details" => [
        "capture" => CaptureRequest::CAPTURE_MANUAL_TYPE,
        "capture_expiry" => "2035-08-26T11:42:00",
        "capture_remain" => 10000,
        "card_expiry_date" => 2202,
        "external_transaction_id" => 1234567980,
        "last4" => 1111,
        "recurring_flow" => null,
        "recurring_id" => "1234567980123123123123",
        "recurring_model" => null,
        "type" => "VISA",
    ],
    "payment_methods" => [
        [
            "amount" => 10000,
            "card_expiry_date" => 2202,
            "currency" => "USD",
            "description" => "Test payment for order #100000001",
            "external_transaction_id" => 1234567980,
            "last4" => 1111,
            "status" => "initialized",
            "type" => "VISA",
        ],
    ],
];
