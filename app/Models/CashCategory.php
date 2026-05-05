<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashCategory extends Model
{
    protected $table = 'cash_categories';

    protected $fillable = ['name', 'type', 'icon', 'color', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function cashFlows(): HasMany
    {
        return $this->hasMany(CashFlow::class, 'category_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeIncome($q)
    {
        return $q->where('type', 'income');
    }

    public function scopeExpense($q)
    {
        return $q->where('type', 'expense');
    }
}
