<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventCoordinator extends Model
{
    protected $table = 'event_coordinators';

    protected $fillable = [
        'event_id',
        'user_id',
        'name',
        'role',
        'phone',
        'email',
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
