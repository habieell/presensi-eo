<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\FaceRegistration;
use App\Services\ActivityLog\ActivityLogger;
use App\Services\FaceRecognition\FaceMatchService;
use Illuminate\Http\Request;

class FaceRegistrationController extends Controller
{
    public function __construct(private FaceMatchService $face) {}

    public function show(Request $request)
    {
        $reg = FaceRegistration::where('user_id', $request->user()->id)->first();
        return response()->json([
            'registered'    => $reg !== null,
            'registered_at' => $reg?->registered_at,
            'quality_score' => $reg?->quality_score,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'descriptor'    => 'required|array|size:128',
            'descriptor.*'  => 'numeric',
            'photo'         => 'nullable|image|max:4096',
            'quality_score' => 'nullable|numeric|min:0|max:1',
        ]);

        // Validasi descriptor format
        try {
            $descriptor = $this->face->validateDescriptor($request->input('descriptor'));
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // Simpan foto referensi (optional)
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('face-registration', 'public');
        }

        $isNew = !FaceRegistration::where('user_id', $user->id)->exists();

        $reg = FaceRegistration::updateOrCreate(
            ['user_id' => $user->id],
            [
                'descriptor'    => $descriptor,
                'photo_path'    => $photoPath,
                'quality_score' => $request->input('quality_score'),
                'is_active'     => true,
                'registered_at' => now(),
            ]
        );

        ActivityLogger::user(
            userId: $user->id,
            action: $isNew ? 'face_registered' : 'face_updated',
            field: 'face_descriptor',
            after: ['quality_score' => $reg->quality_score],
        );

        return response()->json([
            'message'    => $isNew ? 'Wajah berhasil didaftarkan' : 'Wajah berhasil diperbarui',
            'registered' => true,
        ], 201);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        FaceRegistration::where('user_id', $user->id)->delete();
        ActivityLogger::user($user->id, 'face_updated', 'face_descriptor', 'registered', 'deleted');
        return response()->json(['message' => 'Pendaftaran wajah dihapus']);
    }
}
