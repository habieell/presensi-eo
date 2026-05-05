<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\CalendarEvent;
use App\Models\CashFlow;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $today = today();

        $todayIn  = Attendance::where('user_id', $user->id)->whereDate('date', $today)->where('type', 'in')->first();
        $todayOut = Attendance::where('user_id', $user->id)->whereDate('date', $today)->where('type', 'out')->first();

        $month = now()->month;
        $year = now()->year;

        $monthlyAttendance = Attendance::where('user_id', $user->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->where('type', 'in')
            ->count();

        $upcomingEvents = CalendarEvent::upcoming()
            ->where(function ($q) use ($user) {
                $q->where('visibility', 'public')
                  ->orWhereHas('participants', fn($p) => $p->where('user_id', $user->id));
            })
            ->limit(5)
            ->get()
            ->map(fn($e) => $e->toFrontendArray());

        return response()->json([
            'today' => [
                'check_in'  => $todayIn,
                'check_out' => $todayOut,
            ],
            'month_stats' => [
                'present'      => $monthlyAttendance,
                'late'         => Attendance::where('user_id', $user->id)->whereMonth('date', $month)->where('status', 'late')->count(),
                'early_leave'  => Attendance::where('user_id', $user->id)->whereMonth('date', $month)->where('status', 'early_leave')->count(),
            ],
            'face_registered' => $user->faceRegistration()->exists(),
            'upcoming_events' => $upcomingEvents,
        ]);
    }
}
