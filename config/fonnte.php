<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fonnte WhatsApp Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Fonnte adalah layanan WhatsApp gateway lokal yang gampang dipakai.
    | Daftar di https://fonnte.com untuk dapatkan device token.
    |
    */

    'enabled' => env('WHATSAPP_NOTIFICATION_ENABLED', true),

    'token' => env('FONNTE_TOKEN'),

    'base_url' => env('FONNTE_BASE_URL', 'https://api.fonnte.com'),

    'device' => env('FONNTE_DEVICE'),

    'country_code' => env('FONNTE_COUNTRY_CODE', '62'),

    /*
    | Daftar nomor WA admin yang menerima notifikasi penting (comma separated).
    | Format: 628xxxxxxxxxx,628xxxxxxxxxx
    */
    'admin_numbers' => array_filter(
        array_map('trim', explode(',', (string) env('ADMIN_WA_NUMBERS', '')))
    ),

    'timeout' => 15,

    'retry_attempts' => 3,
];
