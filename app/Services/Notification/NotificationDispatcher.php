<?php

namespace App\Services\Notification;

use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\Telegram\TelegramService;
use App\Services\WhatsApp\FonnteService;

/**
 * NotificationDispatcher - one-stop entry point untuk semua notifikasi
 *
 * Channel routing strategy:
 *   - Primary: TELEGRAM (gratis, stable, recommended)
 *   - Secondary: WHATSAPP via Fonnte (kalau aktif & user punya nomor)
 *   - Kedua-duanya bisa aktif sekaligus → kirim ke dua channel
 */
class NotificationDispatcher
{
    public function __construct(
        private TelegramService $tg,
        private FonnteService $wa,
    ) {}

    /** Notif ke ADMIN saat user mengubah / request perubahan profil */
    public function profileChanged(User $user, string $field, $oldValue, $newValue, string $action = 'updated'): void
    {
        $appName = config('app.name');
        $time    = now()->format('d M Y H:i');

        $msg = "🔔 *{$appName}* — Perubahan Data User\n\n"
             . "👤 *User:* {$user->name} ({$user->email})\n"
             . "📝 *Action:* " . strtoupper($action) . "\n"
             . "🔧 *Field:* `{$field}`\n"
             . "↪️ *Lama:* " . $this->fmt($oldValue) . "\n"
             . "✅ *Baru:* " . $this->fmt($newValue) . "\n"
             . "🕒 *Waktu:* {$time}\n\n"
             . "_Cek dashboard admin untuk verifikasi._";

        $opts = [
            'event_type' => 'user.profile_changed',
            'subject'    => "Profile changed: {$user->name}",
            'payload'    => compact('user', 'field', 'oldValue', 'newValue', 'action'),
        ];

        $this->tg->sendToAdmins($msg, $opts);
        $this->wa->sendToAdmins($msg, $opts);
    }

    /** Notif saat user check-in */
    public function attendanceCheckIn(User $user, $attendance): void
    {
        $msg = "✅ *Check-In Berhasil*\n\n"
             . "Halo *{$user->name}*,\n"
             . "Anda berhasil check-in pada:\n"
             . "Tanggal: " . $attendance->date->format('d M Y') . "\n"
             . "Waktu: {$attendance->time}\n"
             . ($attendance->location ? "Lokasi: {$attendance->location}\n" : "")
             . ($attendance->face_verified ? "Verifikasi wajah: " . round($attendance->face_score * 100) . "%\n" : "")
             . "\nSelamat bekerja, semoga produktif!";

        $opts = [
            'event_type' => 'attendance.checkin',
            'subject'    => 'Check-in confirmation',
        ];

        $this->tg->sendToUser($user, $msg, $opts);
        $this->wa->sendToUser($user, $msg, $opts);
    }

    /** Notif saat user check-out */
    public function attendanceCheckOut(User $user, $attendance): void
    {
        $msg = "👋 *Check-Out Berhasil*\n\n"
             . "Halo *{$user->name}*,\n"
             . "Terima kasih atas dedikasi Anda hari ini.\n\n"
             . "Tanggal: " . $attendance->date->format('d M Y') . "\n"
             . "Waktu pulang: {$attendance->time}\n"
             . ($attendance->face_verified ? "Verifikasi wajah: berhasil\n" : "")
             . "\nSelamat beristirahat, sampai jumpa esok hari.";

        $opts = [
            'event_type' => 'attendance.checkout',
            'subject'    => 'Check-out confirmation',
        ];

        $this->tg->sendToUser($user, $msg, $opts);
        $this->wa->sendToUser($user, $msg, $opts);
    }

    /** Notif reminder event ke participants (multi-channel) */
    public function eventReminder(CalendarEvent $event): int
    {
        $sent = 0;
        $event->loadMissing('participants.user');

        foreach ($event->participants as $p) {
            $msg = "📅 *Pengingat Acara*\n\n"
                 . "Halo *{$p->name}*,\n"
                 . "Pengingat untuk acara yang akan datang:\n\n"
                 . "*{$event->title}*\n"
                 . "Waktu: " . $event->event_at->format('d M Y, H:i') . "\n"
                 . ($event->location ? "Lokasi: {$event->location}\n" : "")
                 . ($event->description ? "\n_" . substr($event->description, 0, 100) . "..._\n" : "")
                 . "\nSampai jumpa di acara.";

            $opts = [
                'user_id'    => $p->user_id,
                'event_type' => 'event.reminder',
                'subject'    => "Reminder: {$event->title}",
            ];

            $delivered = false;

            // Coba Telegram dulu
            if ($p->user) {
                $r = $this->tg->sendToUser($p->user, $msg, $opts);
                if ($r['success']) $delivered = true;
            }

            // Fallback / dual channel: WA
            if ($p->user) {
                $r = $this->wa->sendToUser($p->user, $msg, $opts);
                if ($r['success']) $delivered = true;
            } elseif ($p->phone) {
                $target = $this->normalizePhone($p->phone);
                $r = $this->wa->send($target, $msg, $opts);
                if ($r['success']) $delivered = true;
            }

            if ($delivered) {
                $p->update(['reminder_sent' => true]);
                $sent++;
            }
        }

        $event->update(['reminder_sent' => true]);
        return $sent;
    }

    /** Notif ke admin saat ada cash flow baru */
    public function cashFlowChanged(string $action, $cashFlow, ?User $actor = null): void
    {
        $emoji = $cashFlow->type === 'income' ? '💰' : '💸';
        $sign  = $cashFlow->type === 'income' ? '+' : '-';

        $msg = "{$emoji} *Cash Flow {$action}*\n\n"
             . "📝 {$cashFlow->description}\n"
             . "💵 *{$sign}Rp " . number_format((float) $cashFlow->amount, 0, ',', '.') . "*\n"
             . "📅 " . $cashFlow->date->format('d M Y') . "\n"
             . ($cashFlow->category ? "🏷️ Kategori: {$cashFlow->category->name}\n" : "")
             . ($actor ? "👤 By: {$actor->name}\n" : "")
             . "\n_" . config('app.name') . "_";

        $opts = [
            'event_type' => 'cashflow.' . strtolower($action),
            'subject'    => "Cash flow {$action}",
        ];

        $this->tg->sendToAdmins($msg, $opts);
        $this->wa->sendToAdmins($msg, $opts);
    }

    private function fmt($value): string
    {
        if ($value === null || $value === '') return '-';
        if (is_array($value) || is_object($value)) return json_encode($value);
        return (string) $value;
    }

    private function normalizePhone(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($clean, '0')) {
            return config('fonnte.country_code', '62') . substr($clean, 1);
        }
        if (!str_starts_with($clean, config('fonnte.country_code', '62'))) {
            return config('fonnte.country_code', '62') . $clean;
        }
        return $clean;
    }
}
