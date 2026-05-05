<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLog extends Model
{
    public $timestamps = false;

    protected $table = 'user_logs';

    protected $fillable = [
        'user_id',
        'actor_id',
        'action',
        'field',
        'before',
        'after',
        'ip_address',
        'user_agent',
        'notification_sent',
        'logged_at',
    ];

    protected $casts = [
        'before'            => 'array',
        'after'             => 'array',
        'notification_sent' => 'boolean',
        'logged_at'         => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
