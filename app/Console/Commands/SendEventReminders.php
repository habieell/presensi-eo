<?php

namespace App\Console\Commands;

use App\Models\CalendarEvent;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Console\Command;

class SendEventReminders extends Command
{
    protected $signature = 'events:send-reminders';
    protected $description = 'Kirim reminder WhatsApp ke peserta event yang sudah dekat (notify_before menit lagi)';

    public function handle(NotificationDispatcher $notif): int
    {
        $now = now();

        $events = CalendarEvent::where('reminder_sent', false)
            ->where('is_active', true)
            ->where('event_at', '>', $now)
            ->get()
            ->filter(function ($event) use ($now) {
                $minutesUntil = $now->diffInMinutes($event->event_at, false);
                return $minutesUntil <= ($event->notify_before ?? 30) && $minutesUntil > 0;
            });

        $this->info("Memproses {$events->count()} event...");
        $totalSent = 0;
        foreach ($events as $event) {
            $sent = $notif->eventReminder($event);
            $totalSent += $sent;
            $this->line(" - Event #{$event->id} '{$event->title}' → {$sent} pesan terkirim");
        }

        $this->info("✓ Selesai. Total {$totalSent} reminder terkirim.");
        return self::SUCCESS;
    }
}
