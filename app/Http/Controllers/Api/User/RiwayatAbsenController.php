<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;

class RiwayatAbsenController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);

        $rows = Attendance::where('user_id', $user->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->get()
            ->groupBy(fn($a) => $a->date->format('Y-m-d'))
            ->map(function ($byDate) {
                $in  = $byDate->firstWhere('type', 'in');
                $out = $byDate->firstWhere('type', 'out');
                return [
                    'date'      => $byDate->first()->date->format('Y-m-d'),
                    'check_in'  => $in?->only(['time', 'face_verified', 'location', 'status']),
                    'check_out' => $out?->only(['time', 'face_verified', 'location']),
                    'status'    => $in?->status ?? 'absent',
                ];
            })
            ->values();

        return response()->json([
            'month' => $month,
            'year'  => $year,
            'data'  => $rows,
        ]);
    }
}
