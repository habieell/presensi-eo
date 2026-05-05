<?php

use App\Http\Middleware\Cors;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\VerifyCaptcha;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'cors' => Cors::class,
            'verify.captcha' => VerifyCaptcha::class,
        ]);

        $middleware->prepend(Cors::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Force JSON response untuk semua request ke /api/*
        // Jadi tidak ada redirect ke route('login') yang bikin RouteNotFoundException
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated. Token tidak valid atau belum login.',
                    'authenticated' => false,
                ], 401);
            }
        });
    })->create();
