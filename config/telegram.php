<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Configuration
    |--------------------------------------------------------------------------
    |
    | Daftar bot di @BotFather di Telegram → /newbot → kasih nama → dapet token
    | Docs: https://core.telegram.org/bots/api
    |
    */

    'enabled' => env('TELEGRAM_NOTIFICATION_ENABLED', true),

    'bot_token' => env('TELEGRAM_BOT_TOKEN'),

    'base_url' => 'https://api.telegram.org',

    /*
    | Default chat IDs admin (comma separated).
    | Cara cari chat_id: chat dulu sama bot lu, lalu buka:
    |   https://api.telegram.org/bot<TOKEN>/getUpdates
    | Cari "chat":{"id":...}
    */
    'admin_chat_ids' => array_filter(
        array_map('trim', explode(',', (string) env('TELEGRAM_ADMIN_CHAT_IDS', '')))
    ),

    'parse_mode' => 'Markdown',

    'timeout' => 15,

    'retry_attempts' => 3,

    /* Webhook secret untuk verify request dari Telegram (kalau pake webhook) */
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
];
