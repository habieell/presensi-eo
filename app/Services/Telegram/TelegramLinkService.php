<?php

namespace App\Services\Telegram;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * TelegramLinkService — handle proses linking user dengan chat_id Telegram
 *
 * Flow:
 *  1. User klik "Connect Telegram" di profil
 *  2. Backend generate token unik (tk_xxx) untuk user
 *  3. Backend bikin deep link: https://t.me/<bot>?start=tk_xxx
 *  4. User klik link → buka Telegram → klik "START"
 *  5. Telegram kirim ke bot: "/start tk_xxx"
 *  6. Webhook/Polling tangkap message ini → extract token → cari user → save chat_id
 *  7. Bot reply ke user: "✅ Akun lu udah ke-link"
 */
class TelegramLinkService
{
    public function __construct(private TelegramService $tg) {}

    /**
     * Process incoming Telegram update (dari webhook atau polling).
     * Return true kalau ada update yang berhasil di-handle.
     */
    public function processUpdate(array $update): bool
    {
        $message = $update['message'] ?? null;
        if (!$message) return false;

        $chatId   = $message['chat']['id'] ?? null;
        $text     = $message['text'] ?? '';
        $fromUser = $message['from'] ?? [];

        if (!$chatId) return false;

        // Cek apakah user kirim "/start tk_xxx"
        if (preg_match('/^\/start\s+(tk_\w{16})$/', $text, $m)) {
            return $this->linkUser($chatId, $m[1], $fromUser);
        }

        // Cek "/start" tanpa token
        if ($text === '/start') {
            $this->tg->send($chatId, $this->welcomeMessage(), [
                'event_type' => 'telegram.welcome',
            ]);
            return true;
        }

        // Cek "/whoami" — user mau tau chat_id-nya
        if ($text === '/whoami') {
            $msg = "🆔 *Info Telegram Anda*\n\n"
                 . "Chat ID: `{$chatId}`\n"
                 . "Username: @" . ($fromUser['username'] ?? '-') . "\n"
                 . "Nama: " . trim(($fromUser['first_name'] ?? '') . ' ' . ($fromUser['last_name'] ?? '')) . "\n\n"
                 . "_Salin chat ID di atas dan berikan kepada admin._";
            $this->tg->send($chatId, $msg, ['event_type' => 'telegram.whoami']);
            return true;
        }

        return false;
    }

    private function linkUser(string|int $chatId, string $token, array $fromUser): bool
    {
        $user = User::where('telegram_link_token', $token)->first();

        if (!$user) {
            $this->tg->send($chatId, "❌ *Tautan tidak valid atau sudah kedaluwarsa.*\n\nSilakan minta admin untuk membuat tautan baru, atau buka halaman Profil di aplikasi dan klik *Hubungkan Telegram* kembali.", [
                'event_type' => 'telegram.link_invalid',
            ]);
            return false;
        }

        // Update user
        $user->forceFill([
            'telegram_chat_id'      => (string) $chatId,
            'telegram_username'     => $fromUser['username'] ?? null,
            'telegram_link_token'   => null, // hangus setelah dipakai
            'telegram_notification' => true,
            'telegram_linked_at'    => now(),
        ])->save();

        Log::info('Telegram linked', ['user_id' => $user->id, 'chat_id' => $chatId]);

        // Konfirmasi ke user
        $name = $user->name;
        $appName = config('app.name');
        $msg = "✅ *Berhasil Terhubung*\n\n"
             . "Halo *{$name}*,\n"
             . "Akun *{$appName}* Anda telah berhasil terhubung dengan Telegram ini.\n\n"
             . "Mulai sekarang, Anda akan menerima notifikasi:\n"
             . "• Konfirmasi check-in dan check-out\n"
             . "• Pengingat acara yang Anda ikuti\n"
             . "• Pemberitahuan penting dari admin\n\n"
             . "_Silakan kembali ke aplikasi untuk melanjutkan._";

        $this->tg->send($chatId, $msg, [
            'user_id'    => $user->id,
            'event_type' => 'telegram.linked',
        ]);

        return true;
    }

    private function welcomeMessage(): string
    {
        return "👋 *Halo!*\n\n"
             . "Ini adalah bot notifikasi untuk *" . config('app.name') . "*.\n\n"
             . "Untuk menghubungkan akun Anda:\n"
             . "1. Buka aplikasi *" . config('app.name') . "* di browser\n"
             . "2. Login dan buka halaman *Profil*\n"
             . "3. Klik tombol *Hubungkan Telegram*\n"
             . "4. Klik tautan yang muncul, kemudian kembali ke chat ini dan klik *START*\n\n"
             . "Atau ketik /whoami untuk mengetahui chat ID Anda.";
    }
}
