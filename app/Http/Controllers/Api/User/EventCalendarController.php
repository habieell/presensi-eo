<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use Illuminate\Http\Request;

/**
 * Kalender khusus untuk user/peserta - hanya tampilkan event:
 *  - visibility public, ATAU
 *  - user terdaftar sebagai peserta
 */
class EventCalendarController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);

        $events = CalendarEvent::with(['speakers', 'coordinators', 'participants' => fn($p) => $p->where('user_id', $user->id)])
            ->where('is_active', true)
            ->whereMonth('event_at', $month)
            ->whereYear('event_at', $year)
            ->where(function ($q) use ($user) {
                $q->where('visibility', 'public')
                  ->orWhereHas('participants', fn($p) => $p->where('user_id', $user->id));
            })
            ->orderBy('event_at', 'asc')
            ->get();

        return response()->json([
            'month' => $month,
            'year'  => $year,
            'events' => $events->map(function ($e) use ($user) {
                $arr = $e->toFrontendArray();
                $arr['speakers']     = $e->speakers;
                $arr['coordinators'] = $e->coordinators;
                $arr['my_participation'] = $e->participants->first();
                return $arr;
            }),
        ]);
    }

    public function myParticipations(Request $request)
    {
        $user = $request->user();
        $events = CalendarEvent::with(['speakers'])
            ->whereHas('participants', fn($p) => $p->where('user_id', $user->id))
            ->orderBy('event_at', 'desc')
            ->paginate(20);
        return response()->json($events);
    }

    public function confirmAttendance(Request $request, int $eventId)
    {
        $user = $request->user();
        $participant = \App\Models\EventParticipant::where('event_id', $eventId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $participant->update([
            'status'       => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return response()->json(['message' => 'Konfirmasi berhasil', 'data' => $participant]);
    }
}
