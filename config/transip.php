<?php

return [
    // TransIP control panel username.
    'login' => env('TRANSIP_LOGIN'),

    // Private key from the control panel: the PEM contents, or a path to the PEM file.
    'private_key' => env('TRANSIP_PRIVATE_KEY'),

    // Skip key auth and use a fixed access token (e.g. the TransIP demo token).
    'token' => env('TRANSIP_TOKEN'),

    'endpoint' => env('TRANSIP_ENDPOINT', 'https://api.transip.nl/v6'),

    // false = token only works from IP addresses whitelisted in the control panel.
    'global_key' => env('TRANSIP_GLOBAL_KEY', true),

    'read_only' => env('TRANSIP_READ_ONLY', false),

    // Adds ?test=1 to every request: TransIP validates the call but does not execute it.
    'test_mode' => env('TRANSIP_TEST_MODE', false),

    // Token lifetime, max "1 month". Tokens are cached until shortly before they expire.
    'expiration_time' => env('TRANSIP_EXPIRATION_TIME', '1 hour'),

    // Cache store for the access token (null = default store).
    'cache_store' => env('TRANSIP_CACHE_STORE'),
];
