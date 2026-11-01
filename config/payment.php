<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway Configuration
    |--------------------------------------------------------------------------
    */

    'default' => env('PAYMENT_DEFAULT_GATEWAY', 'cod'),

    /*
    |--------------------------------------------------------------------------
    | JazzCash Gateway Settings (MWALLET, Card, OTC)
    |--------------------------------------------------------------------------
    */
    'jazzcash' => [
        'sandbox' => env('JAZZCASH_SANDBOX', true),
        'merchant_id' => env('JAZZCASH_MERCHANT_ID', 'MC_DEMO_2026'),
        'password' => env('JAZZCASH_PASSWORD', 'jazz_demo_pass'),
        'integrity_salt' => env('JAZZCASH_INTEGRITY_SALT', 'flexi_salt_2026_xyz'),
        'sandbox_url' => 'https://sandbox.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/',
        'live_url' => 'https://payments.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/',
        'return_url' => env('APP_URL', 'http://localhost:8000') . '/api/payment/callback/jazzcash',
    ],

    /*
    |--------------------------------------------------------------------------
    | Easypaisa Gateway Settings (Mobile Account, Card)
    |--------------------------------------------------------------------------
    */
    'easypaisa' => [
        'sandbox' => env('EASYPAISA_SANDBOX', true),
        'store_id' => env('EASYPAISA_STORE_ID', 'EP_DEMO_STORE'),
        'hash_key' => env('EASYPAISA_HASH_KEY', 'ep_demo_hash_key_2026'),
        'sandbox_url' => 'https://easypaisa.com.pk/easypay/Index.jsf',
        'live_url' => 'https://easypaisa.com.pk/easypay/Index.jsf',
        'return_url' => env('APP_URL', 'http://localhost:8000') . '/api/payment/callback/easypaisa',
    ],

    /*
    |--------------------------------------------------------------------------
    | PayFast (1Link / APPS) Gateway Settings (All Pakistani Banks & Cards)
    |--------------------------------------------------------------------------
    */
    'payfast' => [
        'sandbox' => env('PAYFAST_SANDBOX', true),
        'merchant_id' => env('PAYFAST_MERCHANT_ID', 'PF_DEMO_1002'),
        'secured_key' => env('PAYFAST_SECURED_KEY', 'pf_secured_key_2026'),
        'sandbox_url' => 'https://ipguat.apps.net.pk/Ecommerce/api/Transaction/GetAccessToken',
        'live_url' => 'https://ipg.apps.net.pk/Ecommerce/api/Transaction/GetAccessToken',
        'return_url' => env('APP_URL', 'http://localhost:8000') . '/api/payment/callback/payfast',
    ],
    /*
    |--------------------------------------------------------------------------
    | Safepay Gateway Settings (All Pakistani Banks, Cards, Wallets)
    |--------------------------------------------------------------------------
    */
    'safepay' => [
        'sandbox' => env('SAFEPAY_SANDBOX', true),
        'api_key' => env('SAFEPAY_API_KEY', 'sec_c7248eab-d56f-4a5f-b04e-c79d1a134e93'),
        'webhook_secret' => env('SAFEPAY_WEBHOOK_SECRET', ''),
        'api_url' => env('SAFEPAY_SANDBOX', true)
            ? 'https://sandbox.api.getsafepay.com'
            : 'https://api.getsafepay.com',
        'checkout_url' => env('SAFEPAY_SANDBOX', true)
            ? 'https://sandbox.api.getsafepay.com/components'
            : 'https://www.getsafepay.com/components',
        'return_url' => env('APP_URL', 'http://localhost:8000') . '/api/payment/callback/safepay',
    ],
];
