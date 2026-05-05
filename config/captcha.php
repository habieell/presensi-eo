<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Captcha Configuration
    |--------------------------------------------------------------------------
    | Built-in lightweight captcha untuk halaman login.
    | TTL = berapa lama captcha valid (detik).
    */

    'enabled' => env('CAPTCHA_ENABLED', true),

    'sensitive' => env('CAPTCHA_SENSITIVE', true),

    'length' => 5,

    'ttl' => 300, // 5 menit

    'characters' => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789',

    'width'  => 160,
    'height' => 60,
];
