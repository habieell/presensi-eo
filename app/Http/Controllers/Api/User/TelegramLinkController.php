<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Endpoint untuk user link/unlink Telegram-nya sendiri
 */
class TelegramLinkController extends Controller
{
    /** GET /api/user/telegram/status */
    public function status(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'linked'             => $user->hasTelegramLinked(),
            'chat_id'            => $user->telegram_chat_id,
            'username'           => $user->telegram_username,
            'linked_at'          => $user->telegram_linked_at,
            'notification_enabled' => (bool) $user->telegram_notification,
        ]);
    }

    /**
     * POST /api/user/telegram/link
     * Generate fresh token + return deep link ke bot
     */
    public function generateLink(Request $request)
    {
        $user = $request->user();

        // Cari username bot lu via getMe
        $bot = $this->getBotUsername();
        if (!$bot) {
            return response()->json([
                'message' => 'Bot Telegram belum dikonfigurasi. Set TELEGRAM_BOT_TOKEN di .env.',
            ], 503);
        }

        $token = $user->generateTelegramLinkToken();
        $deepLink = "https://t.me/{$bot}?start={$token}";

        return response()->json([
            'token'      => $token,
            'deep_link'  => $deepLink,
            'bot_username' => $bot,
            'expires_in' => 600, // 10 menit (informatif - actual expire: kalau ke-replace)
            'instruction' => 'Klik link → buka Telegram → klik tombol START. Akun bakal otomatis ke-link dalam beberapa detik.',
        ]);
    }

    /** DELETE /api/user/telegram/link */
    public function unlink(Request $request)
    {
        $user = $request->user();
        $user->forceFill([
            'telegram_chat_id'    => null,
            'telegram_username'   => null,
            'telegram_link_token' => null,
            'telegram_linked_at'  => null,
        ])->save();
        return response()->json(['message' => 'Telegram di-unlink']);
    }

    /** POST /api/user/telegram/toggle */
    public function toggleNotification(Request $request)
    {
        $request->validate(['enabled' => 'required|boolean']);
        $user = $request->user();
        $user->update(['telegram_notification' => $request->boolean('enabled')]);
        return response()->json(['enabled' => (bool) $user->telegram_notification]);
    }

    private function getBotUsername(): ?string
    {
        $token = config('telegram.bot_token');
        if (!$token) return null;

        return cache()->remember('telegram.bot_username', 3600, function () use ($token) {
            try {
                $r = Http::timeout(5)->get(config('telegram.base_url') . "/bot{$token}/getMe");
                return $r->json('result.username');
            } catch (\Throwable) {
                return null;
            }
        });
    }
}
