<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Telegram\TelegramLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhook handler untuk Telegram Bot.
 *
 * Setup: php artisan telegram:set-webhook https://yourdomain.com/api/telegram/webhook/<secret>
 *
 * Untuk dev local (no public URL): pake polling dengan `php artisan telegram:poll`
 */
class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, string $secret, TelegramLinkService $link)
    {
        // Verify secret biar gak random orang bisa POST
        $expected = config('telegram.webhook_secret') ?: hash('sha256', config('telegram.bot_token') ?? '');
        if (!hash_equals($expected, $secret)) {
            return response()->json(['error' => 'invalid secret'], 403);
        }

        $update = $request->all();
        Log::debug('Telegram webhook received', $update);

        $link->processUpdate($update);

        return response()->json(['ok' => true]);
    }
}
