<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ActivityLog\ActivityLogger;
use App\Services\Captcha\SimpleCaptchaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(private SimpleCaptchaService $captcha) {}

    public function login(Request $request)
    {
        $request->validate([
            'email'          => 'required|email',
            'password'       => 'required',
            'captcha_id'     => config('captcha.enabled') ? 'required|uuid' : 'nullable',
            'captcha_answer' => config('captcha.enabled') ? 'required|string' : 'nullable',
        ]);

        // 1) Captcha check
        if (config('captcha.enabled', true)) {
            $valid = $this->captcha->verify($request->captcha_id, $request->captcha_answer, $request->ip());
            if (!$valid) {
                return response()->json([
                    'message'        => 'Captcha tidak valid atau kedaluwarsa.',
                    'captcha_failed' => true,
                ], 422);
            }
        }

        // 2) Find user
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            if ($user) {
                ActivityLogger::user($user->id, 'failed_login', null, null, ['email' => $request->email]);
            }
            return response()->json(['message' => 'Email atau password salah.'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Akun nonaktif. Hubungi admin.'], 403);
        }

        // 3) Issue token
        $token = $user->createToken('auth-token')->plainTextToken;

        ActivityLogger::user($user->id, 'login', null, null, ['ip' => $request->ip()]);

        return response()->json([
            'user'  => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        ActivityLogger::user($user->id, 'logout');
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('faceRegistration');
        return response()->json(['user' => new UserResource($user)]);
    }
}
