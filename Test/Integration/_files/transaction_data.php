<?php

declare(strict_types=1);

use MultiSafepay\Api\Transactions\Transaction;

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
    "description" => "Test payment for order #100000001",
    "financial_status" => Transaction::COMPLETED,
    "order_id" => "100000001",
    "transaction_id" => 123123123123123123,
    "order_total" => 100,
    "payment_details" => [
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
            "status" => "completed",
            "type" => "VISA",
        ],
        [
            "amount" => 500,
            "coupon_brand" => "VVVBON",
            "currency" => "EUR",
            "description" => "Coupon Intersolve/111112",
            "status" => "completed",
            "type" => "COUPON"
        ]
    ],
    "status" => Transaction::INITIALIZED
];
