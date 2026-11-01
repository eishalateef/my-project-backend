<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default SMS Driver
    |--------------------------------------------------------------------------
    | Supported: "simulation", "twilio", "infobip", "custom_http"
    */
    'default' => env('SMS_DRIVER', 'simulation'),

    /*
    |--------------------------------------------------------------------------
    | Twilio Settings
    |--------------------------------------------------------------------------
    */
    'twilio' => [
        'sid' => env('TWILIO_SID', ''),
        'token' => env('TWILIO_AUTH_TOKEN', ''),
        'from' => env('TWILIO_PHONE_NUMBER', ''), // E.164 format, e.g. +1234567890
    ],

    /*
    |--------------------------------------------------------------------------
    | Infobip Settings
    |--------------------------------------------------------------------------
    */
    'infobip' => [
        'base_url' => env('INFOBIP_BASE_URL', ''), // e.g. abc123.api.infobip.com
        'api_key' => env('INFOBIP_API_KEY', ''),
        'from' => env('INFOBIP_SENDER_ID', 'FlexiStore'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom HTTP / Local Telecom Gateway (Jazz, Telenor, Zong, etc.)
    |--------------------------------------------------------------------------
    | For local SMS APIs that accept a GET or POST request with URL parameters
    | Example: https://api.smsprovider.com/send?api_key=XYZ&to={to}&msg={msg}
    */
    'custom_http' => [
        'url' => env('SMS_CUSTOM_URL', ''),
        'method' => env('SMS_CUSTOM_METHOD', 'POST'), // 'POST' or 'GET'
        'api_key' => env('SMS_CUSTOM_API_KEY', ''),
        'sender_id' => env('SMS_CUSTOM_SENDER_ID', 'FlexiStore'),
        // Parameter mappings for custom gateways
        'param_to' => env('SMS_PARAM_TO', 'to'),
        'param_message' => env('SMS_PARAM_MESSAGE', 'message'),
        'param_api_key' => env('SMS_PARAM_API_KEY', 'api_key'),
        'param_sender_id' => env('SMS_PARAM_SENDER_ID', 'sender_id'),
    ],
];
