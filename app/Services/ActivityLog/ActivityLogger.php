<?php

namespace App\Services\ActivityLog;

use App\Models\AttendanceLog;
use App\Models\CashFlowLog;
use App\Models\UserLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * ActivityLogger - facade tipis untuk insert ke 3 tabel log:
 * user_logs, attendance_logs, cash_flow_logs
 *
 * Dipakai dari Observer atau Controller secara eksplisit.
 */
class ActivityLogger
{
    /**
     * Log perubahan pada user
     */
    public static function user(
        int $userId,
        string $action,
        ?string $field = null,
        $before = null,
        $after = null,
        ?int $actorId = null,
        bool $notificationSent = false
    ): UserLog {
        $req = self::request();

        return UserLog::create([
            'user_id'    => $userId,
            'actor_id'   => $actorId ?? optional($req?->user())->id,
            'action'     => $action,
            'field'      => $field,
            'before'     => $before,
            'after'      => $after,
            'ip_address' => $req?->ip(),
            'user_agent' => $req?->userAgent() ? substr($req->userAgent(), 0, 255) : null,
            'notification_sent' => $notificationSent,
            'logged_at'  => now(),
        ]);
    }

    /**
     * Log perubahan pada attendance
     */
    public static function attendance(
        int $userId,
        string $action,
        ?int $attendanceId = null,
        array $payload = [],
        ?array $changes = null,
        ?int $actorId = null,
        bool $faceVerified = false,
        ?float $faceScore = null
    ): AttendanceLog {
        $req = self::request();

        return AttendanceLog::create([
            'attendance_id' => $attendanceId,
            'user_id'    => $userId,
            'actor_id'   => $actorId ?? optional($req?->user())->id ?? $userId,
            'action'     => $action,
            'payload'    => $payload,
            'changes'    => $changes,
            'ip_address' => $req?->ip(),
            'user_agent' => $req?->userAgent() ? substr($req->userAgent(), 0, 255) : null,
            'device'     => $req?->header('User-Agent') ? self::parseDevice($req->header('User-Agent')) : null,
            'face_verified' => $faceVerified,
            'face_score'    => $faceScore,
            'logged_at'  => now(),
        ]);
    }

    /**
     * Log perubahan pada cash flow
     */
    public static function cashFlow(
        ?int $cashFlowId,
        string $action,
        ?array $before = null,
        ?array $after = null,
        ?float $amount = null,
        ?string $type = null,
        ?int $actorId = null
    ): CashFlowLog {
        $req = self::request();

        return CashFlowLog::create([
            'cash_flow_id' => $cashFlowId,
            'actor_id'     => $actorId ?? optional($req?->user())->id ?? 0,
            'action'       => $action,
            'before'       => $before,
            'after'        => $after,
            'amount'       => $amount,
            'type'         => $type,
            'ip_address'   => $req?->ip(),
            'user_agent'   => $req?->userAgent() ? substr($req->userAgent(), 0, 255) : null,
            'logged_at'    => now(),
        ]);
    }

    private static function request(): ?Request
    {
        try {
            return App::has('request') ? request() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function parseDevice(string $ua): string
    {
        if (preg_match('/iPhone|iPad/i', $ua)) return 'iOS';
        if (preg_match('/Android/i', $ua)) return 'Android';
        if (preg_match('/Macintosh/i', $ua)) return 'macOS';
        if (preg_match('/Windows/i', $ua)) return 'Windows';
        if (preg_match('/Linux/i', $ua)) return 'Linux';
        return 'Unknown';
    }
}
