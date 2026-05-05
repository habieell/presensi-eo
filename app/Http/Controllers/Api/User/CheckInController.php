<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Services\FaceRecognition\FaceMatchService;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckInController extends Controller
{
    public function __construct(
        private FaceMatchService $face,
        private NotificationDispatcher $notif,
    ) {}

    public function store(Request $request)
    {
        $user  = $request->user();
        $today = now()->toDateString();

        // 1) Cek belum check-in hari ini
        $existing = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->where('type', 'in')
            ->exists();
        if ($existing) {
            return response()->json(['message' => 'Anda sudah check-in hari ini'], 400);
        }

        // 2) Validasi input
        $request->validate([
            'photo'         => 'nullable|image|max:4096',
            'face_descriptor' => 'nullable|array',
            'face_descriptor.*' => 'numeric',
            'location'      => 'nullable|string|max:255',
            'latitude'      => 'nullable|numeric',
            'longitude'     => 'nullable|numeric',
        ]);

        // 3) Face verification (wajib bila enabled)
        $faceVerified = false;
        $faceScore = null;

        if (config('face.enabled', true) && config('face.required_for.check_in', true)) {
            if (!$request->has('face_descriptor')) {
                return response()->json([
                    'message' => 'Face descriptor diperlukan untuk check-in',
                    'face_required' => true,
                ], 422);
            }
            $match = $this->face->matchUser($user, $request->input('face_descriptor'));
            $faceVerified = $match['matched'];
            $faceScore = $match['score'];

            if (!$faceVerified) {
                // log percobaan gagal
                \App\Services\ActivityLog\ActivityLogger::attendance(
                    userId: $user->id,
                    action: 'face_failed',
                    payload: ['distance' => $match['distance'], 'score' => $match['score']],
                    faceVerified: false,
                    faceScore: $match['score'],
                );
                return response()->json([
                    'message' => 'Verifikasi wajah gagal. Pastikan wajah jelas & sesuai pendaftaran.',
                    'face_verified' => false,
                    'score'    => $match['score'],
                    'distance' => $match['distance'],
                    'threshold'=> (float) config('face.match_threshold'),
                ], 422);
            }
        }

        // 4) Simpan
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('attendance', 'public');
        }

        $startTime = \App\Models\Setting::get('work_start_time', '08:00:00');
        $isLate = now()->format('H:i:s') > $startTime;

        $attendance = DB::transaction(function () use ($user, $today, $photoPath, $faceVerified, $faceScore, $request, $isLate) {
            return Attendance::create([
                'user_id'       => $user->id,
                'type'          => 'in',
                'date'          => $today,
                'time'          => now()->toTimeString(),
                'photo_path'    => $photoPath,
                'face_verified' => $faceVerified,
                'face_score'    => $faceScore,
                'location'      => $request->input('location'),
                'latitude'      => $request->input('latitude'),
                'longitude'     => $request->input('longitude'),
                'status'        => $isLate ? 'late' : 'on_time',
            ]);
        });

        // 5) Notif WA
        $this->notif->attendanceCheckIn($user, $attendance);

        return response()->json([
            'message'    => 'Check-in berhasil',
            'attendance' => $attendance,
        ], 201);
    }
}
