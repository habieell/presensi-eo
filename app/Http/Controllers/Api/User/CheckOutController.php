<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Setting;
use App\Services\FaceRecognition\FaceMatchService;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckOutController extends Controller
{
    public function __construct(
        private FaceMatchService $face,
        private NotificationDispatcher $notif,
    ) {}

    public function store(Request $request)
    {
        $user  = $request->user();
        $today = now()->toDateString();

        // Wajib sudah check-in
        $hasIn = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->where('type', 'in')
            ->exists();
        if (!$hasIn) {
            return response()->json(['message' => 'Belum ada check-in untuk hari ini'], 404);
        }

        // Belum check-out
        $hasOut = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->where('type', 'out')
            ->exists();
        if ($hasOut) {
            return response()->json(['message' => 'Anda sudah check-out hari ini'], 400);
        }

        $request->validate([
            'photo'         => 'nullable|image|max:4096',
            'face_descriptor' => 'nullable|array',
            'face_descriptor.*' => 'numeric',
            'location'      => 'nullable|string|max:255',
            'latitude'      => 'nullable|numeric',
            'longitude'     => 'nullable|numeric',
        ]);

        // Face verification
        $faceVerified = false;
        $faceScore = null;

        if (config('face.enabled', true) && config('face.required_for.check_out', true)) {
            if (!$request->has('face_descriptor')) {
                return response()->json([
                    'message' => 'Face descriptor diperlukan untuk check-out',
                    'face_required' => true,
                ], 422);
            }
            $match = $this->face->matchUser($user, $request->input('face_descriptor'));
            $faceVerified = $match['matched'];
            $faceScore = $match['score'];

            if (!$faceVerified) {
                \App\Services\ActivityLog\ActivityLogger::attendance(
                    userId: $user->id,
                    action: 'face_failed',
                    payload: ['mode' => 'checkout', 'distance' => $match['distance']],
                    faceVerified: false,
                    faceScore: $match['score'],
                );
                return response()->json([
                    'message' => 'Verifikasi wajah gagal',
                    'face_verified' => false,
                    'score' => $match['score'],
                ], 422);
            }
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('attendance', 'public');
        }

        $endTime = Setting::get('work_end_time', '17:00:00');
        $isEarly = now()->format('H:i:s') < $endTime;

        $attendance = DB::transaction(function () use ($user, $today, $photoPath, $faceVerified, $faceScore, $request, $isEarly) {
            return Attendance::create([
                'user_id'       => $user->id,
                'type'          => 'out',
                'date'          => $today,
                'time'          => now()->toTimeString(),
                'photo_path'    => $photoPath,
                'face_verified' => $faceVerified,
                'face_score'    => $faceScore,
                'location'      => $request->input('location'),
                'latitude'      => $request->input('latitude'),
                'longitude'     => $request->input('longitude'),
                'status'        => $isEarly ? 'early_leave' : 'on_time',
            ]);
        });

        $this->notif->attendanceCheckOut($user, $attendance);

        return response()->json([
            'message'    => 'Check-out berhasil',
            'attendance' => $attendance,
        ], 201);
    }
}
