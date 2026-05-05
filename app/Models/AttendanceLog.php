<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    public $timestamps = false;

    protected $table = 'attendance_logs';

    protected $fillable = [
        'attendance_id',
        'user_id',
        'actor_id',
        'action',
        'payload',
        'changes',
        'ip_address',
        'user_agent',
        'device',
        'face_verified',
        'face_score',
        'logged_at',
    ];

    protected $casts = [
        'payload'       => 'array',
        'changes'       => 'array',
        'face_verified' => 'boolean',
        'face_score'    => 'float',
        'logged_at'     => 'datetime',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
