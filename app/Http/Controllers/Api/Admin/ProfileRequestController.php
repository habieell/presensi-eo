<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfileUpdateRequest;
use App\Services\ActivityLog\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileRequestController extends Controller
{
    public function index(Request $request)
    {
        $q = ProfileUpdateRequest::with('user:id,name,email');
        if ($status = $request->input('status', 'pending')) $q->where('status', $status);
        return response()->json($q->orderBy('requested_at', 'desc')->paginate(20));
    }

    public function approve(Request $request, ProfileUpdateRequest $profileUpdateRequest)
    {
        DB::transaction(function () use ($request, $profileUpdateRequest) {
            $user = $profileUpdateRequest->user;
            $user->update([$profileUpdateRequest->field => $profileUpdateRequest->new_value]);
            $profileUpdateRequest->update([
                'status'      => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'admin_notes' => $request->input('notes'),
            ]);
            ActivityLogger::user($user->id, 'profile_approved', $profileUpdateRequest->field, null, $profileUpdateRequest->new_value);
        });
        return response()->json(['message' => 'Disetujui']);
    }

    public function reject(Request $request, ProfileUpdateRequest $profileUpdateRequest)
    {
        $profileUpdateRequest->update([
            'status'      => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'admin_notes' => $request->input('notes'),
        ]);
        ActivityLogger::user($profileUpdateRequest->user_id, 'profile_rejected', $profileUpdateRequest->field);
        return response()->json(['message' => 'Ditolak']);
    }
}
