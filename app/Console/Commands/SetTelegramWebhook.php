<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SetTelegramWebhook extends Command
{
    protected $signature = 'telegram:set-webhook {url? : Public webhook URL, default APP_URL/api/telegram/webhook/{secret}} {--delete : Hapus webhook}';
    protected $description = 'Set/hapus webhook Telegram bot. Untuk production.';

    public function handle(): int
    {
        $token = config('telegram.bot_token');
        if (!$token) { $this->error('TELEGRAM_BOT_TOKEN missing'); return self::FAILURE; }

        $base = config('telegram.base_url') . "/bot{$token}";

        if ($this->option('delete')) {
            $r = Http::post("{$base}/deleteWebhook");
            $this->info('Webhook deleted: ' . json_encode($r->json()));
            return self::SUCCESS;
        }

        $secret  = config('telegram.webhook_secret') ?: hash('sha256', $token);
        $appUrl  = rtrim(config('app.url'), '/');
        $url     = $this->argument('url') ?: "{$appUrl}/api/telegram/webhook/{$secret}";

        $this->info("Setting webhook to: {$url}");
        $r = Http::post("{$base}/setWebhook", ['url' => $url, 'drop_pending_updates' => true]);
        $body = $r->json();

        if ($body['ok'] ?? false) {
            $this->info('✓ Webhook berhasil di-set');
        } else {
            $this->error('✗ Gagal: ' . json_encode($body));
        }

        return self::SUCCESS;
    }
}
