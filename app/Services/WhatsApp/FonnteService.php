<?php

namespace App\Services\WhatsApp;

use App\Models\NotificationOutbox;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * FonnteService - WhatsApp Gateway via Fonnte API
 *
 * Docs: https://fonnte.com/api-send-message
 *
 * Endpoint utama: POST {base_url}/send
 * Headers: Authorization: <device-token>
 * Body: target=628xxx&message=...
 */
class FonnteService
{
    private string $token;
    private string $baseUrl;
    private bool $enabled;

    public function __construct()
    {
        $this->token   = (string) config('fonnte.token');
        $this->baseUrl = (string) config('fonnte.base_url');
        $this->enabled = (bool) config('fonnte.enabled');
    }

    /**
     * Kirim pesan WA ke nomor tunggal atau array nomor
     *
     * @param string|array $target nomor (628xxx) atau array nomor
     * @param string $message
     * @param array $opts ['user_id' => ?, 'event_type' => '...', 'subject' => '...']
     * @return array{success:bool, response:mixed, outbox_id:int|null}
     */
    public function send(string|array $target, string $message, array $opts = []): array
    {
        $targetStr = is_array($target) ? implode(',', $target) : $target;

        // Always log to outbox even before sending
        $outbox = NotificationOutbox::create([
            'channel'    => 'whatsapp',
            'recipient'  => $targetStr,
            'user_id'    => $opts['user_id'] ?? null,
            'event_type' => $opts['event_type'] ?? 'manual',
            'subject'    => $opts['subject'] ?? null,
            'message'    => $message,
            'payload'    => $opts['payload'] ?? null,
            'status'     => 'pending',
        ]);

        if (!$this->enabled) {
            $outbox->update(['status' => 'failed', 'response' => 'WhatsApp notification disabled']);
            Log::info('WA disabled - skipped', ['outbox_id' => $outbox->id]);
            return ['success' => false, 'response' => 'disabled', 'outbox_id' => $outbox->id];
        }

        if (empty($this->token)) {
            $outbox->update(['status' => 'failed', 'response' => 'FONNTE_TOKEN missing']);
            Log::warning('FONNTE_TOKEN kosong, skip kirim WA');
            return ['success' => false, 'response' => 'no token', 'outbox_id' => $outbox->id];
        }

        try {
            $response = Http::timeout(config('fonnte.timeout', 15))
                ->withHeaders(['Authorization' => $this->token])
                ->asForm()
                ->retry(config('fonnte.retry_attempts', 3), 500)
                ->post($this->baseUrl . '/send', [
                    'target'  => $targetStr,
                    'message' => $message,
                    'countryCode' => config('fonnte.country_code', '62'),
                ]);

            $body = $response->json() ?? ['raw' => $response->body()];
            $success = $response->successful() && (($body['status'] ?? false) === true);

            $outbox->update([
                'status'   => $success ? 'sent' : 'failed',
                'response' => json_encode($body),
                'attempts' => $outbox->attempts + 1,
                'sent_at'  => $success ? now() : null,
            ]);

            return ['success' => $success, 'response' => $body, 'outbox_id' => $outbox->id];

        } catch (Throwable $e) {
            Log::error('Fonnte send error', ['error' => $e->getMessage(), 'target' => $targetStr]);
            $outbox->update([
                'status'   => 'failed',
                'response' => $e->getMessage(),
                'attempts' => $outbox->attempts + 1,
            ]);
            return ['success' => false, 'response' => $e->getMessage(), 'outbox_id' => $outbox->id];
        }
    }

    /**
     * Kirim ke seorang user (ambil nomor WA-nya)
     */
    public function sendToUser(User $user, string $message, array $opts = []): array
    {
        if (!$user->whatsapp_notification) {
            Log::info('User WA notification disabled', ['user_id' => $user->id]);
            return ['success' => false, 'response' => 'user disabled', 'outbox_id' => null];
        }

        $target = $user->getWhatsappDestination();
        if (!$target) {
            return ['success' => false, 'response' => 'no WA number', 'outbox_id' => null];
        }

        return $this->send($target, $message, array_merge(['user_id' => $user->id], $opts));
    }

    /**
     * Broadcast ke semua admin yang terdaftar di config('fonnte.admin_numbers')
     */
    public function sendToAdmins(string $message, array $opts = []): array
    {
        $admins = config('fonnte.admin_numbers', []);
        if (empty($admins)) {
            // fallback: ambil semua user role admin yang punya WA
            $admins = User::where('role', 'admin')
                ->where('whatsapp_notification', true)
                ->pluck('whatsapp_number', 'phone')
                ->toArray();
            $admins = array_filter($admins);
        }

        if (empty($admins)) {
            Log::warning('Tidak ada admin WA terdaftar');
            return ['success' => false, 'response' => 'no admins', 'outbox_id' => null];
        }

        return $this->send(array_values($admins), $message, array_merge(['event_type' => 'admin.broadcast'], $opts));
    }
}
