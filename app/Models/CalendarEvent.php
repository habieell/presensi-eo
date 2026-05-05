<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class CalendarEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'calendar_events';

    protected $fillable = [
        'title',
        'description',
        'location',
        'event_at',
        'event_end_at',
        'notify_before',
        'category',
        'visibility',
        'color',
        'created_by',
        'reminder_sent',
        'is_active',
    ];

    protected $casts = [
        'event_at'      => 'datetime',
        'event_end_at'  => 'datetime',
        'reminder_sent' => 'boolean',
        'is_active'     => 'boolean',
    ];

    // ==== ONE-TO-MANY RELATIONSHIPS ====

    public function coordinators(): HasMany
    {
        return $this->hasMany(EventCoordinator::class, 'event_id');
    }

    public function speakers(): HasMany
    {
        return $this->hasMany(EventSpeaker::class, 'event_id')->orderBy('order');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(EventParticipant::class, 'event_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==== SCOPES ====

    public function scopeUpcoming($q)
    {
        return $q->where('event_at', '>=', now())->where('is_active', true);
    }

    public function scopeForParticipant($q, int $userId)
    {
        return $q->whereHas('participants', fn($p) => $p->where('user_id', $userId));
    }

    public function toFrontendArray(): array
    {
        return [
            'id'             => $this->id,
            'title'          => $this->title,
            'description'    => $this->description,
            'location'       => $this->location,
            'event_at'       => $this->event_at?->toISOString(),
            'event_end_at'   => $this->event_end_at?->toISOString(),
            'notify_before'  => $this->notify_before ?? 30,
            'category'       => $this->category,
            'visibility'     => $this->visibility,
            'color'          => $this->color,
            'created_by'     => $this->created_by,
            'creator_name'   => $this->creator?->name,
            'reminder_sent'  => (bool) $this->reminder_sent,
            'is_active'      => (bool) $this->is_active,
            'coordinators'   => $this->whenLoaded('coordinators', fn() => $this->coordinators),
            'speakers'       => $this->whenLoaded('speakers', fn() => $this->speakers),
            'participants_count' => $this->participants()->count(),
            'created_at'     => $this->created_at?->toISOString(),
            'updated_at'     => $this->updated_at?->toISOString(),
        ];
    }

    private function whenLoaded(string $rel, \Closure $closure)
    {
        return $this->relationLoaded($rel) ? $closure() : null;
    }
}
