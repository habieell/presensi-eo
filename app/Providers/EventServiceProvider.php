<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Models\CashFlow;
use App\Models\User;
use App\Observers\AttendanceObserver;
use App\Observers\CashFlowObserver;
use App\Observers\UserObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [];

    public function boot(): void
    {
        User::observe(UserObserver::class);
        Attendance::observe(AttendanceObserver::class);
        CashFlow::observe(CashFlowObserver::class);
    }
}
