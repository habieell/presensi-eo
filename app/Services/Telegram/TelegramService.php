<?php

namespace App\Services\Telegram;

use App\Models\NotificationOutbox;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * TelegramService - Bot Telegram via Bot API resmi
 *
 * Endpoint: https://api.telegram.org/bot<TOKEN>/sendMessage
 * Body JSON: { chat_id, text, parse_mode }
 *
 * Setup:
 *  1) Buka @BotFather → /newbot → ikuti instruksi → dapet TOKEN
 *  2) Chat dulu sama bot lu (apa aja, mis. /start)
 *  3) Buka https://api.telegram.org/bot<TOKEN>/getUpdates → cari chat.id
 *  4) Set di .env:
 *     TELEGRAM_BOT_TOKEN=...
 *     TELEGRAM_ADMIN_CHAT_IDS=123456789
 */
class TelegramService
{
    private string $token;
    private string $baseUrl;
    private bool $enabled;

    public function __construct()
    {
        $this->token   = (string) config('telegram.bot_token');
        $this->baseUrl = (string) config('telegram.base_url');
        $this->enabled = (bool) config('telegram.enabled');
    }

    /**
     * Kirim pesan ke chat_id (single atau array)
     *
     * @return array{success:bool, response:mixed, outbox_id:int|null}
     */
    public function send(string|int|array $chatId, string $message, array $opts = []): array
    {
        $targets = is_array($chatId) ? $chatId : [$chatId];
        $results = [];

        foreach ($targets as $target) {
            $results[] = $this->sendOne((string) $target, $message, $opts);
        }

        // Aggregate
        $allSuccess = collect($results)->every(fn($r) => $r['success']);
        return [
            'success'   => $allSuccess,
            'response'  => $results,
            'outbox_id' => $results[0]['outbox_id'] ?? null,
        ];
    }

    private function sendOne(string $chatId, string $message, array $opts): array
    {
        $outbox = NotificationOutbox::create([
            'channel'    => 'telegram',
            'recipient'  => $chatId,
            'user_id'    => $opts['user_id'] ?? null,
            'event_type' => $opts['event_type'] ?? 'manual',
            'subject'    => $opts['subject'] ?? null,
            'message'    => $message,
            'payload'    => $opts['payload'] ?? null,
            'status'     => 'pending',
        ]);

        if (!$this->enabled) {
            $outbox->update(['status' => 'failed', 'response' => 'Telegram disabled']);
            return ['success' => false, 'response' => 'disabled', 'outbox_id' => $outbox->id];
        }

        if (empty($this->token)) {
            $outbox->update(['status' => 'failed', 'response' => 'TELEGRAM_BOT_TOKEN missing']);
            Log::warning('TELEGRAM_BOT_TOKEN kosong');
            return ['success' => false, 'response' => 'no token', 'outbox_id' => $outbox->id];
        }

        try {
            $url = "{$this->baseUrl}/bot{$this->token}/sendMessage";
            $response = Http::timeout(config('telegram.timeout', 15))
                ->retry(config('telegram.retry_attempts', 3), 500)
                ->post($url, [
                    'chat_id'    => $chatId,
                    'text'       => $message,
                    'parse_mode' => config('telegram.parse_mode', 'Markdown'),
                    'disable_web_page_preview' => true,
                ]);

            $body = $response->json() ?? ['raw' => $response->body()];
            $success = $response->successful() && (($body['ok'] ?? false) === true);

            $outbox->update([
                'status'   => $success ? 'sent' : 'failed',
                'response' => json_encode($body),
                'attempts' => $outbox->attempts + 1,
                'sent_at'  => $success ? now() : null,
            ]);

            return ['success' => $success, 'response' => $body, 'outbox_id' => $outbox->id];

        } catch (Throwable $e) {
            Log::error('Telegram send error', ['error' => $e->getMessage(), 'chat_id' => $chatId]);
            $outbox->update([
                'status'   => 'failed',
                'response' => $e->getMessage(),
                'attempts' => $outbox->attempts + 1,
            ]);
            return ['success' => false, 'response' => $e->getMessage(), 'outbox_id' => $outbox->id];
        }
    }

    /** Kirim ke seorang user via telegram_chat_id-nya */
    public function sendToUser(User $user, string $message, array $opts = []): array
    {
        if (!$user->telegram_notification) {
            return ['success' => false, 'response' => 'user disabled', 'outbox_id' => null];
        }
        if (!$user->telegram_chat_id) {
            return ['success' => false, 'response' => 'no chat_id', 'outbox_id' => null];
        }
        return $this->send($user->telegram_chat_id, $message, array_merge(['user_id' => $user->id], $opts));
    }

    /** Broadcast ke semua admin (dari config + user role admin yg punya chat_id) */
    public function sendToAdmins(string $message, array $opts = []): array
    {
        $ids = config('telegram.admin_chat_ids', []);

        // Tambahkan dari user table juga
        $userIds = User::where('role', 'admin')
            ->where('telegram_notification', true)
            ->whereNotNull('telegram_chat_id')
            ->pluck('telegram_chat_id')
            ->toArray();

        $allIds = array_unique(array_merge($ids, $userIds));

        if (empty($allIds)) {
            Log::warning('Tidak ada admin Telegram chat_id terdaftar');
            return ['success' => false, 'response' => 'no admins', 'outbox_id' => null];
        }

        return $this->send(array_values($allIds), $message, array_merge([
            'event_type' => 'admin.broadcast',
        ], $opts));
    }

    /**
     * Helper untuk cari chat_id user yang baru chat sama bot
     * Berguna saat onboarding user baru
     */
    public function getUpdates(): array
    {
        if (empty($this->token)) return [];
        try {
            $r = Http::timeout(10)->get("{$this->baseUrl}/bot{$this->token}/getUpdates");
            return $r->json('result') ?? [];
        } catch (Throwable) {
            return [];
        }
    }
}
