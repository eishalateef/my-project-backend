<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FlexiStore Pro Agency & Developer Controls
    |--------------------------------------------------------------------------
    |
    | These settings allow you (the developer/agency) to control store types
    | directly in code or .env, and lock/unlock features for your clients.
    |
    */

    // Default dedicated store type
    'default_store_type' => 'clothing',

    // Dedicated single-store setup: Multi-store switching permanently disabled
    'enable_store_switcher' => false,

    // Developer Secret Key: Allows YOU to override locks anytime via
    // ?dev_key=... or developer admin unlock modal
    'developer_secret_key' => env('DEVELOPER_SECRET_KEY', 'flexi_dev_secret_2026'),
];
