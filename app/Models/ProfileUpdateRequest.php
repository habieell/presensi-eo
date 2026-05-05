<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileUpdateRequest extends Model
{
    public $timestamps = false;

    protected $table = 'profile_update_requests';

    protected $fillable = [
        'user_id',
        'field',
        'old_value',
        'new_value',
        'status',
        'admin_notes',
        'approved_by',
        'requested_at',
        'approved_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($q) { return $q->where('status', 'pending'); }
}
