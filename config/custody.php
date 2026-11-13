<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Active Custody Provider Driver
    |--------------------------------------------------------------------------
    | Supported: "hsm_http", "fireblocks", "bitgo", "fordefi", "mock"
    | SECURITY: "mock" is strictly blocked when APP_ENV=production.
    */
    'driver' => env('CUSTODY_DRIVER', 'hsm_http'),
    'testnet_only' => (bool) env('CUSTODY_TESTNET_ONLY', true),
    'environment' => env('CUSTODY_ENV', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Dedicated Redis / Queue Names
    |--------------------------------------------------------------------------
    */
    'queues' => [
        'monitoring' => env('CRYPTO_QUEUE_MONITORING', 'monitoring'),
        'verification' => env('CRYPTO_QUEUE_VERIFICATION', 'verification'),
        'crediting' => env('CRYPTO_QUEUE_CREDITING', 'crediting'),
        'signing' => env('CRYPTO_QUEUE_SIGNING', 'signing'),
        'sweeping' => env('CRYPTO_QUEUE_SWEEPING', 'sweeping'),
        'recovery' => env('CRYPTO_QUEUE_RECOVERY', 'recovery'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Generic Signed mTLS / HMAC HSM-MPC HTTP Service
    |--------------------------------------------------------------------------
    */
    'hsm_http' => [
        'base_url' => env('HSM_HTTP_BASE_URL', ''),
        'hmac_secret' => env('HSM_HTTP_HMAC_SECRET', ''),
        'api_key' => env('HSM_HTTP_API_KEY', ''),
        'mtls_cert_path' => env('HSM_HTTP_MTLS_CERT', ''),
        'mtls_key_path' => env('HSM_HTTP_MTLS_KEY', ''),
        'mtls_ca_path' => env('HSM_HTTP_MTLS_CA', ''),
        'timeout_seconds' => (int) env('HSM_HTTP_TIMEOUT', 15),
        'allowed_ips' => array_values(array_filter(explode(',', (string) env('HSM_HTTP_ALLOWED_IPS', '')))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Modular Vendor Custody Adapters (Credentials via ENV only)
    |--------------------------------------------------------------------------
    */
    'fireblocks' => [
        'base_url' => env('FIREBLOCKS_BASE_URL', 'https://api.fireblocks.io'),
        'api_key' => env('FIREBLOCKS_API_KEY', ''),
        'private_key_path' => env('FIREBLOCKS_API_SECRET_PATH', ''),
        'vault_account_id' => env('FIREBLOCKS_VAULT_ACCOUNT_ID', ''),
        'timeout_seconds' => (int) env('FIREBLOCKS_TIMEOUT', 20),
    ],

    'bitgo' => [
        'base_url' => env('BITGO_BASE_URL', 'https://app.bitgo.com/api/v2'),
        'access_token' => env('BITGO_ACCESS_TOKEN', ''),
        'enterprise_id' => env('BITGO_ENTERPRISE_ID', ''),
        'timeout_seconds' => (int) env('BITGO_TIMEOUT', 20),
    ],

    'fordefi' => [
        'base_url' => env('FORDEFI_BASE_URL', 'https://api.fordefi.com'),
        'api_token' => env('FORDEFI_API_TOKEN', ''),
        'private_key_path' => env('FORDEFI_API_SIGNER_KEY_PATH', ''),
        'vault_id' => env('FORDEFI_VAULT_ID', ''),
        'timeout_seconds' => (int) env('FORDEFI_TIMEOUT', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Outbound Signing & Treasury Policies
    |--------------------------------------------------------------------------
    */
    'policies' => [
        'require_admin_2fa_for_sensitive_ops' => (bool) env('CUSTODY_REQUIRE_ADMIN_2FA', true),
        'max_gas_sponsor_per_address_base_units' => (string) env('MAX_GAS_SPONSOR_BASE_UNITS', '5000000000000000'), // 0.005 ETH/BNB
        'min_sweep_threshold_base_units' => (string) env('MIN_SWEEP_THRESHOLD_BASE_UNITS', '1'),
        'max_daily_sweep_base_units' => (string) env('MAX_DAILY_SWEEP_BASE_UNITS', '10000000000000000000000000'),
        'max_single_sweep_base_units' => [
            'BTC' => (string) env('MAX_SWEEP_BTC_SATS', '500000000'),       // 5 BTC
            'ETH' => (string) env('MAX_SWEEP_ETH_WEI', '50000000000000000000'), // 50 ETH
            'BNB' => (string) env('MAX_SWEEP_BNB_WEI', '500000000000000000000'), // 500 BNB
            'TRX' => (string) env('MAX_SWEEP_TRX_SUN', '1000000000000'),   // 1,000,000 TRX
            'SOL' => (string) env('MAX_SWEEP_SOL_LAMPORTS', '1000000000000'), // 1000 SOL
            'LTC' => (string) env('MAX_SWEEP_LTC_LITS', '5000000000'),     // 50 LTC
            'DOGE' => (string) env('MAX_SWEEP_DOGE_KOINU', '10000000000000'), // 100k DOGE
            'XRP' => (string) env('MAX_SWEEP_XRP_DROPS', '100000000000'),  // 100k XRP
            'DEFAULT' => (string) env('MAX_SWEEP_DEFAULT_BASE_UNITS', '1000000000000000000000000'),
        ],
    ],
];

