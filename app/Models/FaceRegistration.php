<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceRegistration extends Model
{
    protected $table = 'face_registrations';

    protected $fillable = [
        'user_id',
        'descriptor',
        'photo_path',
        'quality_score',
        'is_active',
        'registered_at',
    ];

    protected $casts = [
        'descriptor'    => 'array',
        'quality_score' => 'float',
        'is_active'     => 'boolean',
        'registered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
