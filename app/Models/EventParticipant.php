<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventParticipant extends Model
{
    protected $table = 'event_participants';

    protected $fillable = [
        'event_id',
        'user_id',
        'name',
        'email',
        'phone',
        'institution',
        'status',
        'registered_at',
        'confirmed_at',
        'attended_at',
        'reminder_sent',
        'notes',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'confirmed_at'  => 'datetime',
        'attended_at'   => 'datetime',
        'reminder_sent' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'event_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeConfirmed($q) { return $q->where('status', 'confirmed'); }
    public function scopeAttended($q)  { return $q->where('status', 'attended'); }
}
