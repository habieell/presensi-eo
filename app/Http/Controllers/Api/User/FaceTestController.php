<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\FaceRecognition\FaceMatchService;
use Illuminate\Http\Request;

/**
 * Endpoint dev/test untuk cek seberapa mirip wajah dengan pendaftaran user.
 * Berguna buat tuning threshold sebelum check-in beneran.
 */
class FaceTestController extends Controller
{
    public function __construct(private FaceMatchService $face) {}

    public function compare(Request $request)
    {
        $request->validate([
            'descriptor'   => 'required|array|size:128',
            'descriptor.*' => 'numeric',
        ]);

        $match = $this->face->matchUser($request->user(), $request->input('descriptor'));

        return response()->json([
            'matched'   => $match['matched'],
            'distance'  => $match['distance'],
            'score'     => $match['score'],
            'threshold' => (float) config('face.match_threshold'),
            'verdict'   => $match['matched']
                ? "✅ MATCH - distance {$match['distance']} < threshold " . config('face.match_threshold')
                : "❌ NO MATCH - distance {$match['distance']} >= threshold " . config('face.match_threshold'),
        ]);
    }
}
