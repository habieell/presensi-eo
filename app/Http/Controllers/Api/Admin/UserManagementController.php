<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $q = User::query()->with('faceRegistration');

        if ($search = $request->input('q')) {
            $q->where(function ($query) use ($search) {
                $query->where('name', 'like', "%$search%")
                      ->orWhere('email', 'like', "%$search%")
                      ->orWhere('nik', 'like', "%$search%");
            });
        }
        if ($role = $request->input('role')) $q->where('role', $role);
        if ($request->has('is_active')) $q->where('is_active', $request->boolean('is_active'));

        return UserResource::collection($q->orderBy('name')->paginate(15));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users',
            'nik'       => 'nullable|unique:users',
            'password'  => 'required|min:6',
            'role'      => 'required|in:admin,user',
            'position'  => 'nullable|string',
            'phone'     => 'nullable|string',
            'address'   => 'nullable|string',
            'whatsapp_number' => 'nullable|string',
            'telegram_chat_id' => 'nullable|string|max:50',
            'telegram_username' => 'nullable|string|max:100',
        ]);
        $data['password']     = Hash::make($data['password']);
        $data['is_active']    = true;
        $data['is_confirmed'] = true;

        $user = User::create($data); // observer akan log + notif
        return new UserResource($user);
    }

    public function show(User $user)
    {
        return new UserResource($user->load('faceRegistration'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'      => 'sometimes|string|max:255',
            'email'     => 'sometimes|email|unique:users,email,' . $user->id,
            'nik'       => 'sometimes|nullable|unique:users,nik,' . $user->id,
            'password'  => 'sometimes|nullable|min:6',
            'role'      => 'sometimes|in:admin,user',
            'position'  => 'sometimes|nullable|string',
            'phone'     => 'sometimes|nullable|string',
            'address'   => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
            'whatsapp_number' => 'sometimes|nullable|string',
            'whatsapp_notification' => 'sometimes|boolean',
            'telegram_chat_id' => 'sometimes|nullable|string|max:50',
            'telegram_username' => 'sometimes|nullable|string|max:100',
            'telegram_notification' => 'sometimes|boolean',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data); // observer akan log + notif
        return new UserResource($user->fresh());
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'User dihapus']);
    }
}
