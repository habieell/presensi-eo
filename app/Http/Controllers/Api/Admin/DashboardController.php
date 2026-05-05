<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\CalendarEvent;
use App\Models\CashFlow;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = today();
        $startMonth = now()->startOfMonth();
        $endMonth = now()->endOfMonth();

        $totalUsers = User::where('role', 'user')->count();
        $activeUsers = User::where('role', 'user')->where('is_active', true)->count();
        $todayPresent = Attendance::whereDate('date', $today)->where('type', 'in')->distinct('user_id')->count('user_id');

        $monthlyIncome = CashFlow::whereBetween('date', [$startMonth, $endMonth])->where('type', 'income')->where('status', 'approved')->sum('amount');
        $monthlyExpense = CashFlow::whereBetween('date', [$startMonth, $endMonth])->where('type', 'expense')->where('status', 'approved')->sum('amount');

        $upcomingEvents = CalendarEvent::upcoming()->limit(5)->get()->map(fn($e) => $e->toFrontendArray());

        // Chart kehadiran 7 hari terakhir
        $attendanceChart = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo);
            return [
                'date'    => $date->format('Y-m-d'),
                'label'   => $date->format('D'),
                'present' => Attendance::whereDate('date', $date)->where('type', 'in')->distinct('user_id')->count('user_id'),
                'late'    => Attendance::whereDate('date', $date)->where('status', 'late')->distinct('user_id')->count('user_id'),
            ];
        });

        return response()->json([
            'stats' => [
                'total_users'     => $totalUsers,
                'active_users'    => $activeUsers,
                'today_present'   => $todayPresent,
                'today_absent'    => $activeUsers - $todayPresent,
                'monthly_income'  => (float) $monthlyIncome,
                'monthly_expense' => (float) $monthlyExpense,
                'monthly_balance' => (float) ($monthlyIncome - $monthlyExpense),
                'upcoming_events_count' => CalendarEvent::upcoming()->count(),
            ],
            'attendance_chart' => $attendanceChart,
            'upcoming_events'  => $upcomingEvents,
        ]);
    }
}
