<?php

namespace App\Observers;

use App\Models\CashFlow;
use App\Services\ActivityLog\ActivityLogger;
use App\Services\Notification\NotificationDispatcher;

class CashFlowObserver
{
    public function __construct(private NotificationDispatcher $notif) {}

    public function created(CashFlow $cashFlow): void
    {
        ActivityLogger::cashFlow(
            cashFlowId: $cashFlow->id,
            action:     'created',
            after:      $cashFlow->only(['description', 'amount', 'type', 'date', 'category_id', 'created_by']),
            amount:     (float) $cashFlow->amount,
            type:       $cashFlow->type,
        );

        $cashFlow->loadMissing('category');
        $this->notif->cashFlowChanged('Created', $cashFlow, $cashFlow->creator);
    }

    public function updated(CashFlow $cashFlow): void
    {
        $changes = $cashFlow->getChanges();
        unset($changes['updated_at']);
        if (empty($changes)) return;

        $original = collect($cashFlow->getOriginal())->only(array_keys($changes))->all();

        ActivityLogger::cashFlow(
            cashFlowId: $cashFlow->id,
            action:     'updated',
            before:     $original,
            after:      $changes,
            amount:     (float) $cashFlow->amount,
            type:       $cashFlow->type,
        );
    }

    public function deleted(CashFlow $cashFlow): void
    {
        ActivityLogger::cashFlow(
            cashFlowId: $cashFlow->id,
            action:     'deleted',
            before:     $cashFlow->only(['description', 'amount', 'type', 'date']),
            amount:     (float) $cashFlow->amount,
            type:       $cashFlow->type,
        );
    }
}
