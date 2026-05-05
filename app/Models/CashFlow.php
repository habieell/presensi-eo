<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashFlow extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cash_flow';

    protected $fillable = [
        'category_id',
        'description',
        'amount',
        'type',
        'date',
        'reference_no',
        'attachment_path',
        'created_by',
        'approved_by',
        'status',
    ];

    protected $casts = [
        'date'   => 'date',
        'amount' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CashCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CashFlowLog::class);
    }

    public function scopeIncome($q) { return $q->where('type', 'income'); }
    public function scopeExpense($q) { return $q->where('type', 'expense'); }
    public function scopeApproved($q) { return $q->where('status', 'approved'); }
}
