<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\Captcha\SimpleCaptchaService;
use Illuminate\Http\Request;

class CaptchaController extends Controller
{
    public function __construct(private SimpleCaptchaService $captcha) {}

    public function generate(Request $request)
    {
        $data = $this->captcha->generate($request->ip());
        return response()->json($data);
    }
}
