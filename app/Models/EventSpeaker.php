<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventSpeaker extends Model
{
    protected $table = 'event_speakers';

    protected $fillable = [
        'event_id',
        'user_id',
        'name',
        'title',
        'organization',
        'topic',
        'bio',
        'photo_path',
        'start_time',
        'end_time',
        'order',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'event_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
