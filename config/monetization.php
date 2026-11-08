<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | Supported: "sandbox", "ccbill", "segpay", "stripe_adult_approved"
    | In adult personal classified platforms, real processing is disabled
    | until verified written approval for the adult classified model is on file.
    |
    */
    'default' => env('PAYMENT_GATEWAY', 'sandbox'),

    'currency' => env('PAYMENT_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Business Model Compliance & Approval Gate
    |--------------------------------------------------------------------------
    |
    | High-risk merchant acquirers require explicit underwriting approval.
    | Real card transactions cannot be executed if this is false.
    |
    */
    'adult_merchant_approved' => (bool) env('PAYMENT_ADULT_MERCHANT_APPROVED', false),

    /*
    |--------------------------------------------------------------------------
    | Testing & Sandboxing
    |--------------------------------------------------------------------------
    */
    'test_mode' => (bool) env('PAYMENT_TEST_MODE', true),

    'allow_free_listings' => (bool) env('ALLOW_FREE_LISTINGS', true),

    /*
    |--------------------------------------------------------------------------
    | Fraud & Velocity Controls
    |--------------------------------------------------------------------------
    */
    'fraud' => [
        'max_charges_per_ip_hour' => (int) env('PAYMENT_MAX_CHARGES_IP_HOUR', 5),
        'max_declines_per_user_hour' => (int) env('PAYMENT_MAX_DECLINES_HOUR', 3),
        'high_risk_threshold' => 70,
        'blocked_risk_threshold' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform Billing & Remit Entity Details (for Invoices)
    |--------------------------------------------------------------------------
    */
    'platform_remit' => [
        'company_name' => 'LocalAdZone Legal Operations, LLC',
        'address' => '100 Enterprise Way, Suite 400',
        'city' => 'Wilmington',
        'state' => 'DE',
        'zip' => '19801',
        'country' => 'United States',
        'support_email' => 'billing@localadzone.com',
        'tax_id' => 'XX-XXXXXXX',
    ],

    /*
    |--------------------------------------------------------------------------
    | Gateways Configuration
    |--------------------------------------------------------------------------
    */
    'gateways' => [
        'sandbox' => [
            'name' => 'Sandbox Simulation Gateway',
            'driver' => \App\Modules\Billing\Gateways\SandboxGateway::class,
            'webhook_secret' => env('SANDBOX_WEBHOOK_SECRET', 'sandbox_webhook_secret_key_12345'),
        ],

        'ccbill' => [
            'name' => 'CCBill High-Risk Gateway',
            'driver' => \App\Modules\Billing\Gateways\CcbillGateway::class,
            'client_account' => env('CCBILL_CLIENT_ACCOUNT', ''),
            'client_subaccount' => env('CCBILL_SUBACCOUNT', ''),
            'salt_key' => env('CCBILL_SALT_KEY', ''),
            'flexform_id' => env('CCBILL_FLEXFORM_ID', ''),
            'webhook_secret' => env('CCBILL_WEBHOOK_SECRET', ''),
        ],

        'segpay' => [
            'name' => 'Segpay Adult Payment Gateway',
            'driver' => \App\Modules\Billing\Gateways\SegpayGateway::class,
            'merchant_id' => env('SEGPAY_MERCHANT_ID', ''),
            'package_id' => env('SEGPAY_PACKAGE_ID', ''),
            'price_id' => env('SEGPAY_PRICE_ID', ''),
            'webhook_secret' => env('SEGPAY_WEBHOOK_SECRET', ''),
        ],

        'stripe_adult_approved' => [
            'name' => 'Stripe (Adult Classified Underwritten)',
            'driver' => \App\Modules\Billing\Gateways\StripeAdultGateway::class,
            'secret_key' => env('STRIPE_SECRET_KEY', ''),
            'publishable_key' => env('STRIPE_KEY', ''),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
        ],
    ],
];
