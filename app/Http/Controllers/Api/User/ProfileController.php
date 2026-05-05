<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\ProfileUpdateRequest;
use App\Services\ActivityLog\ActivityLogger;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function __construct(private NotificationDispatcher $notif) {}

    public function show(Request $request)
    {
        $user = $request->user()->load('faceRegistration');
        $pending = $user->profileUpdateRequests()->pending()->get();
        return response()->json([
            'user'             => new UserResource($user),
            'pending_requests' => $pending,
        ]);
    }

    public function requestUpdate(Request $request)
    {
        $request->validate([
            'field'     => 'required|in:name,address,position,bio,phone,avatar,whatsapp_number,password',
            'new_value' => 'required',
        ]);

        $user = $request->user();
        $field = $request->input('field');
        $newValue = $request->input('new_value');
        $oldValue = $user->{$field} ?? null;

        // Hash password sebelum disimpan
        if ($field === 'password') {
            $newValue = Hash::make($newValue);
            $oldValue = '***';
        }

        $req = ProfileUpdateRequest::create([
            'user_id'      => $user->id,
            'field'        => $field,
            'old_value'    => $oldValue,
            'new_value'    => $newValue,
            'status'       => 'pending',
            'requested_at' => now(),
        ]);

        ActivityLogger::user($user->id, 'profile_request', $field, $oldValue, $field === 'password' ? '***' : $newValue);

        // Notify admin
        $this->notif->profileChanged($user, $field, $oldValue, $field === 'password' ? '***' : $newValue, 'requested');

        return response()->json([
            'message' => 'Permintaan perubahan dikirim ke admin',
            'request' => $req,
        ], 201);
    }

    public function updateDirect(Request $request)
    {
        // Endpoint update langsung untuk field non-sensitif (avatar, bio)
        $request->validate([
            'avatar' => 'nullable|image|max:2048',
            'bio'    => 'nullable|string|max:1000',
            'whatsapp_notification' => 'nullable|boolean',
        ]);

        $user = $request->user();
        $data = [];

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }
        if ($request->has('bio')) $data['bio'] = $request->input('bio');
        if ($request->has('whatsapp_notification')) $data['whatsapp_notification'] = $request->boolean('whatsapp_notification');

        if (!empty($data)) {
            $user->update($data); // observer akan log + notif
        }

        return response()->json(['user' => new UserResource($user->fresh())]);
    }
}
