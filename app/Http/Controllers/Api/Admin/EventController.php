<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\EventCoordinator;
use App\Models\EventParticipant;
use App\Models\EventSpeaker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $q = CalendarEvent::with(['speakers', 'coordinators', 'creator:id,name'])
            ->withCount('participants');

        if ($month = $request->input('month')) $q->whereMonth('event_at', $month);
        if ($year = $request->input('year')) $q->whereYear('event_at', $year);
        if ($cat = $request->input('category')) $q->where('category', $cat);

        return response()->json($q->orderBy('event_at', 'desc')->paginate(20));
    }

    public function show($id)
    {
        $event = CalendarEvent::with(['speakers', 'coordinators', 'participants.user:id,name,email,phone'])
            ->findOrFail($id);
        return response()->json($event);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'location'      => 'nullable|string|max:255',
            'event_at'      => 'required|date',
            'event_end_at'  => 'nullable|date|after_or_equal:event_at',
            'notify_before' => 'nullable|integer|min:0',
            'category'      => 'nullable|in:meeting,training,workshop,seminar,event,other',
            'visibility'    => 'nullable|in:public,participants_only,private',
            'color'         => 'nullable|string|max:9',
            // Nested arrays
            'coordinators'         => 'nullable|array',
            'coordinators.*.name'  => 'required_with:coordinators|string|max:255',
            'coordinators.*.user_id' => 'nullable|exists:users,id',
            'coordinators.*.phone' => 'nullable|string',
            'coordinators.*.email' => 'nullable|email',
            'coordinators.*.role'  => 'nullable|string',

            'speakers'             => 'nullable|array',
            'speakers.*.name'      => 'required_with:speakers|string|max:255',
            'speakers.*.user_id'   => 'nullable|exists:users,id',
            'speakers.*.title'     => 'nullable|string',
            'speakers.*.organization' => 'nullable|string',
            'speakers.*.topic'     => 'nullable|string',
            'speakers.*.bio'       => 'nullable|string',

            'participants'         => 'nullable|array',
            'participants.*.name'  => 'required_with:participants|string|max:255',
            'participants.*.user_id' => 'nullable|exists:users,id',
            'participants.*.email' => 'nullable|email',
            'participants.*.phone' => 'nullable|string',
            'participants.*.institution' => 'nullable|string',
        ]);

        $event = DB::transaction(function () use ($data, $request) {
            $event = CalendarEvent::create([
                'title'         => $data['title'],
                'description'   => $data['description'] ?? null,
                'location'      => $data['location'] ?? null,
                'event_at'      => $data['event_at'],
                'event_end_at'  => $data['event_end_at'] ?? null,
                'notify_before' => $data['notify_before'] ?? 30,
                'category'      => $data['category'] ?? 'event',
                'visibility'    => $data['visibility'] ?? 'public',
                'color'         => $data['color'] ?? '#7c3aed',
                'created_by'    => $request->user()->id,
            ]);

            foreach ($data['coordinators'] ?? [] as $c) {
                EventCoordinator::create(array_merge($c, ['event_id' => $event->id]));
            }
            foreach ($data['speakers'] ?? [] as $i => $s) {
                EventSpeaker::create(array_merge($s, ['event_id' => $event->id, 'order' => $i]));
            }
            foreach ($data['participants'] ?? [] as $p) {
                EventParticipant::create(array_merge($p, [
                    'event_id'      => $event->id,
                    'status'        => 'invited',
                    'registered_at' => now(),
                ]));
            }
            return $event;
        });

        return response()->json($event->load(['coordinators', 'speakers', 'participants']), 201);
    }

    public function update(Request $request, CalendarEvent $event)
    {
        $data = $request->validate([
            'title'         => 'sometimes|string|max:255',
            'description'   => 'sometimes|nullable|string',
            'location'      => 'sometimes|nullable|string|max:255',
            'event_at'      => 'sometimes|date',
            'event_end_at'  => 'sometimes|nullable|date',
            'notify_before' => 'sometimes|integer|min:0',
            'category'      => 'sometimes|in:meeting,training,workshop,seminar,event,other',
            'visibility'    => 'sometimes|in:public,participants_only,private',
            'color'         => 'sometimes|nullable|string|max:9',
            'is_active'     => 'sometimes|boolean',
            'reminder_sent' => 'sometimes|boolean',
        ]);
        $event->update($data);
        return response()->json($event->fresh()->load(['coordinators', 'speakers', 'participants']));
    }

    public function destroy(CalendarEvent $event)
    {
        $event->delete();
        return response()->json(['message' => 'Event dihapus']);
    }

    public function stats()
    {
        $now = now();
        return response()->json([
            'total'    => CalendarEvent::count(),
            'today'    => CalendarEvent::whereDate('event_at', $now)->count(),
            'upcoming' => CalendarEvent::where('event_at', '>=', $now)->count(),
            'past'     => CalendarEvent::where('event_at', '<', $now)->count(),
        ]);
    }

    // ===== Speakers =====
    public function addSpeaker(Request $request, CalendarEvent $event)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'user_id'      => 'nullable|exists:users,id',
            'title'        => 'nullable|string',
            'organization' => 'nullable|string',
            'topic'        => 'nullable|string',
            'bio'          => 'nullable|string',
            'order'        => 'nullable|integer',
        ]);
        $sp = $event->speakers()->create($data);
        return response()->json($sp, 201);
    }

    public function removeSpeaker(CalendarEvent $event, EventSpeaker $speaker)
    {
        abort_unless($speaker->event_id === $event->id, 404);
        $speaker->delete();
        return response()->json(['message' => 'Pembicara dihapus']);
    }

    // ===== Coordinators =====
    public function addCoordinator(Request $request, CalendarEvent $event)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'role'    => 'nullable|string',
            'phone'   => 'nullable|string',
            'email'   => 'nullable|email',
        ]);
        $co = $event->coordinators()->create($data);
        return response()->json($co, 201);
    }

    public function removeCoordinator(CalendarEvent $event, EventCoordinator $coordinator)
    {
        abort_unless($coordinator->event_id === $event->id, 404);
        $coordinator->delete();
        return response()->json(['message' => 'Penanggung jawab dihapus']);
    }

    // ===== Participants =====
    public function addParticipant(Request $request, CalendarEvent $event)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'user_id'     => 'nullable|exists:users,id',
            'email'       => 'nullable|email',
            'phone'       => 'nullable|string',
            'institution' => 'nullable|string',
            'status'      => 'nullable|in:invited,confirmed,attended,absent,cancelled',
        ]);
        $data['registered_at'] = now();
        $data['status'] = $data['status'] ?? 'invited';
        $p = $event->participants()->create($data);
        return response()->json($p, 201);
    }

    public function updateParticipant(Request $request, CalendarEvent $event, EventParticipant $participant)
    {
        abort_unless($participant->event_id === $event->id, 404);
        $data = $request->validate([
            'status' => 'sometimes|in:invited,confirmed,attended,absent,cancelled',
            'notes'  => 'sometimes|nullable|string',
        ]);
        if (($data['status'] ?? null) === 'attended') {
            $data['attended_at'] = now();
        }
        $participant->update($data);
        return response()->json($participant);
    }

    public function removeParticipant(CalendarEvent $event, EventParticipant $participant)
    {
        abort_unless($participant->event_id === $event->id, 404);
        $participant->delete();
        return response()->json(['message' => 'Peserta dihapus']);
    }
}
