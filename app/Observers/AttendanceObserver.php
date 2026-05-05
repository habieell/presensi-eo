<?php

namespace App\Observers;

use App\Models\Attendance;
use App\Services\ActivityLog\ActivityLogger;

class AttendanceObserver
{
    public function created(Attendance $a): void
    {
        ActivityLogger::attendance(
            userId:       $a->user_id,
            action:       $a->type === 'in' ? 'check_in' : 'check_out',
            attendanceId: $a->id,
            payload:      $a->only(['date', 'time', 'location', 'latitude', 'longitude', 'status']),
            faceVerified: (bool) $a->face_verified,
            faceScore:    $a->face_score,
        );
    }

    public function updated(Attendance $a): void
    {
        $changes = $a->getChanges();
        if (empty($changes)) return;

        ActivityLogger::attendance(
            userId:       $a->user_id,
            action:       'updated',
            attendanceId: $a->id,
            payload:      $a->toArray(),
            changes:      $changes,
        );
    }

    public function deleted(Attendance $a): void
    {
        ActivityLogger::attendance(
            userId:       $a->user_id,
            action:       'deleted',
            attendanceId: $a->id,
            payload:      $a->only(['date', 'time', 'type']),
        );
    }
}
