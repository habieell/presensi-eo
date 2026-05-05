<?php

namespace App\Http\Middleware;

use App\Services\Captcha\SimpleCaptchaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCaptcha
{
    public function __construct(private SimpleCaptchaService $captcha) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (!config('captcha.enabled', true)) {
            return $next($request);
        }

        $id     = $request->input('captcha_id');
        $answer = $request->input('captcha_answer');

        if (!$this->captcha->verify($id, $answer, $request->ip())) {
            return response()->json([
                'message'        => 'Captcha tidak valid atau kedaluwarsa',
                'captcha_failed' => true,
            ], 422);
        }

        return $next($request);
    }
}
