<?php

namespace App\Console\Commands;

use App\Models\NotificationOutbox;
use App\Services\WhatsApp\FonnteService;
use Illuminate\Console\Command;

class RetryFailedNotifications extends Command
{
    protected $signature = 'notifications:retry {--max=3 : Max retry per item}';
    protected $description = 'Retry failed notifications dari outbox';

    public function handle(FonnteService $fonnte): int
    {
        $max = (int) $this->option('max');
        $items = NotificationOutbox::where('channel', 'whatsapp')
            ->where('status', 'failed')
            ->where('attempts', '<', $max)
            ->latest()
            ->limit(50)
            ->get();

        $this->info("Mencoba ulang {$items->count()} notifikasi...");
        foreach ($items as $item) {
            $result = $fonnte->send($item->recipient, $item->message, [
                'user_id'    => $item->user_id,
                'event_type' => $item->event_type,
                'subject'    => $item->subject,
                'payload'    => $item->payload,
            ]);
            // Mark original
            $item->update(['attempts' => $item->attempts + 1]);
        }
        $this->info('✓ Selesai retry');
        return self::SUCCESS;
    }
}
