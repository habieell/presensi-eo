<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Admin\{ActivityLogController, AttendanceController as AdminAttendance, CashCategoryController, CashFlowController as AdminCashFlow, DashboardController as AdminDashboard, EventController as AdminEvent, ProfileRequestController, SettingController, UserManagementController};
use App\Http\Controllers\Api\Auth\{AuthController, CaptchaController};
use App\Http\Controllers\Api\User\{CashFlowController as UserCashFlow, CheckInController, CheckOutController, DashboardController as UserDashboard, EventCalendarController, FaceRegistrationController, ProfileController, RiwayatAbsenController, TelegramLinkController};
use App\Http\Controllers\Api\TelegramWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes - Presensi
|--------------------------------------------------------------------------
*/

// ─── PUBLIC ───────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::get('captcha', [CaptchaController::class, 'generate']);
    Route::post('login', [AuthController::class, 'login']);
});

// Telegram webhook (public, di-protect via secret di URL)
Route::post('telegram/webhook/{secret}', TelegramWebhookController::class);

// ─── AUTHENTICATED ────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Shared (admin & user) - lookup categories
    Route::get('cash-categories', [CashCategoryController::class, 'index']);

    // ──────────────── ADMIN ────────────────
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('dashboard', [AdminDashboard::class, 'index']);

        // Users
        Route::apiResource('users', UserManagementController::class);

        // Profile requests
        Route::get('profile-requests', [ProfileRequestController::class, 'index']);
        Route::post('profile-requests/{profileUpdateRequest}/approve', [ProfileRequestController::class, 'approve']);
        Route::post('profile-requests/{profileUpdateRequest}/reject', [ProfileRequestController::class, 'reject']);

        // Attendance
        Route::get('attendance', [AdminAttendance::class, 'index']);
        Route::get('attendance/export', [AdminAttendance::class, 'export']);
        Route::get('attendance/export-pdf', [AdminAttendance::class, 'exportPdf']);

        // Cash flow
        Route::get('cash-flow', [AdminCashFlow::class, 'index']);
        Route::post('cash-flow', [AdminCashFlow::class, 'store']);
        Route::put('cash-flow/{cashFlow}', [AdminCashFlow::class, 'update']);
        Route::delete('cash-flow/{cashFlow}', [AdminCashFlow::class, 'destroy']);
        Route::get('cash-flow/export', [AdminCashFlow::class, 'export']);
        Route::get('cash-flow/export-pdf', [AdminCashFlow::class, 'exportPdf']);

        // Cash categories
        Route::apiResource('cash-categories', CashCategoryController::class)->except(['show']);

        // Events
        Route::get('events/stats', [AdminEvent::class, 'stats']);
        Route::apiResource('events', AdminEvent::class);
        // Event nested - one-to-many
        Route::post('events/{event}/speakers', [AdminEvent::class, 'addSpeaker']);
        Route::delete('events/{event}/speakers/{speaker}', [AdminEvent::class, 'removeSpeaker']);
        Route::post('events/{event}/coordinators', [AdminEvent::class, 'addCoordinator']);
        Route::delete('events/{event}/coordinators/{coordinator}', [AdminEvent::class, 'removeCoordinator']);
        Route::post('events/{event}/participants', [AdminEvent::class, 'addParticipant']);
        Route::put('events/{event}/participants/{participant}', [AdminEvent::class, 'updateParticipant']);
        Route::delete('events/{event}/participants/{participant}', [AdminEvent::class, 'removeParticipant']);

        // Activity logs
        Route::get('logs/users', [ActivityLogController::class, 'user']);
        Route::get('logs/attendance', [ActivityLogController::class, 'attendance']);
        Route::get('logs/cash-flow', [ActivityLogController::class, 'cashFlow']);

        // Settings
        Route::get('settings', [SettingController::class, 'index']);
        Route::put('settings', [SettingController::class, 'update']);
    });

    // ──────────────── USER ────────────────
    Route::middleware('role:user,admin')->prefix('user')->group(function () {
        Route::get('dashboard', [UserDashboard::class, 'index']);

        // Profile
        Route::get('profile', [ProfileController::class, 'show']);
        Route::post('profile/request-update', [ProfileController::class, 'requestUpdate']);
        Route::post('profile/update', [ProfileController::class, 'updateDirect']);

        // Face
        Route::get('face/status', [FaceRegistrationController::class, 'show']);
        Route::post('face/register', [FaceRegistrationController::class, 'store']);
        Route::delete('face/register', [FaceRegistrationController::class, 'destroy']);
        Route::post('face/test', [\App\Http\Controllers\Api\User\FaceTestController::class, 'compare']);

        // Telegram self-connect
        Route::get('telegram/status', [TelegramLinkController::class, 'status']);
        Route::post('telegram/link', [TelegramLinkController::class, 'generateLink']);
        Route::delete('telegram/link', [TelegramLinkController::class, 'unlink']);
        Route::post('telegram/toggle', [TelegramLinkController::class, 'toggleNotification']);

        // Attendance
        Route::post('check-in', [CheckInController::class, 'store']);
        Route::post('check-out', [CheckOutController::class, 'store']);
        Route::get('riwayat-absen', [RiwayatAbsenController::class, 'index']);

        // Cash flow (own)
        Route::get('cash-flow', [UserCashFlow::class, 'index']);
        Route::post('cash-flow', [UserCashFlow::class, 'store']);

        // Calendar khusus peserta
        Route::get('calendar', [EventCalendarController::class, 'index']);
        Route::get('calendar/my-participations', [EventCalendarController::class, 'myParticipations']);
        Route::post('calendar/{eventId}/confirm', [EventCalendarController::class, 'confirmAttendance']);
    });
});
