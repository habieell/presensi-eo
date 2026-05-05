<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashFlowLog extends Model
{
    public $timestamps = false;

    protected $table = 'cash_flow_logs';

    protected $fillable = [
        'cash_flow_id',
        'actor_id',
        'action',
        'before',
        'after',
        'amount',
        'type',
        'ip_address',
        'user_agent',
        'logged_at',
    ];

    protected $casts = [
        'before'    => 'array',
        'after'     => 'array',
        'amount'    => 'decimal:2',
        'logged_at' => 'datetime',
    ];

    public function cashFlow(): BelongsTo
    {
        return $this->belongsTo(CashFlow::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
