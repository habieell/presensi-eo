<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramLinkService;
use App\Services\Telegram\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Polling Telegram getUpdates secara terus-menerus untuk dev local.
 *
 * Run: php artisan telegram:poll
 * Atau: php artisan telegram:poll --once   (sekali jalan, untuk cron)
 *
 * Production: pake webhook (lebih efisien) — set via:
 *   php artisan telegram:set-webhook https://yourdomain.com/api/telegram/webhook/<secret>
 */
class PollTelegramUpdates extends Command
{
    protected $signature = 'telegram:poll {--once : Jalan sekali aja (untuk cron)} {--timeout=30 : Long-polling timeout dalam detik}';
    protected $description = 'Polling Telegram getUpdates buat dev local (gak butuh webhook)';

    public function handle(TelegramLinkService $link, TelegramService $tg): int
    {
        $token = config('telegram.bot_token');
        if (!$token) {
            $this->error('TELEGRAM_BOT_TOKEN belum di-set di .env');
            return self::FAILURE;
        }

        $base = config('telegram.base_url') . "/bot{$token}";
        $offset = (int) Cache::get('telegram.update_offset', 0);
        $once = $this->option('once');
        $timeout = (int) $this->option('timeout');

        $this->info($once ? 'Polling sekali...' : "Long-polling started (timeout: {$timeout}s). Press Ctrl+C to stop.");

        do {
            try {
                $r = Http::timeout($timeout + 5)->get("{$base}/getUpdates", [
                    'offset'  => $offset,
                    'timeout' => $timeout,
                    'limit'   => 50,
                ]);

                $updates = $r->json('result') ?? [];
                if (count($updates) > 0) {
                    $this->line('  → ' . count($updates) . ' update diterima');
                    foreach ($updates as $update) {
                        $offset = max($offset, ($update['update_id'] ?? 0) + 1);
                        $handled = $link->processUpdate($update);
                        if ($handled) {
                            $this->line("    ✓ handled update #{$update['update_id']}");
                        }
                    }
                    Cache::forever('telegram.update_offset', $offset);
                }
            } catch (\Throwable $e) {
                $this->error('Error: ' . $e->getMessage());
                sleep(3);
            }
        } while (!$once);

        return self::SUCCESS;
    }
}
