<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $table = 'attendance';

    protected $fillable = [
        'user_id',
        'type',
        'date',
        'time',
        'photo_path',
        'face_verified',
        'face_score',
        'location',
        'latitude',
        'longitude',
        'status',
        'notes',
    ];

    protected $casts = [
        'date'          => 'date',
        'face_verified' => 'boolean',
        'face_score'    => 'float',
        'latitude'      => 'float',
        'longitude'     => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function scopeToday($q)
    {
        return $q->whereDate('date', today());
    }

    public function scopeForUser($q, int $userId)
    {
        return $q->where('user_id', $userId);
    }
}
