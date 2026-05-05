<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'nik',
        'position',
        'email',
        'phone',
        'address',
        'bio',
        'avatar',
        'role',
        'is_active',
        'is_confirmed',
        'whatsapp_number',
        'whatsapp_notification',
        'telegram_chat_id',
        'telegram_username',
        'telegram_notification',
        'telegram_link_token',
        'telegram_linked_at',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at'    => 'datetime',
        'is_active'            => 'boolean',
        'is_confirmed'         => 'boolean',
        'whatsapp_notification'=> 'boolean',
        'telegram_notification'=> 'boolean',
        'telegram_linked_at'   => 'datetime',
        'password'             => 'hashed',
    ];

    // ==== RELATIONSHIPS ====

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function faceRegistration(): HasOne
    {
        return $this->hasOne(FaceRegistration::class)->where('is_active', true);
    }

    public function profileUpdateRequests(): HasMany
    {
        return $this->hasMany(ProfileUpdateRequest::class);
    }

    public function cashFlows(): HasMany
    {
        return $this->hasMany(CashFlow::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(UserLog::class);
    }

    public function eventParticipations(): HasMany
    {
        return $this->hasMany(EventParticipant::class);
    }

    // ==== HELPERS ====

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /** Format WA: prioritas whatsapp_number, fallback ke phone */
    public function getWhatsappDestination(): ?string
    {
        $raw = $this->whatsapp_number ?: $this->phone;
        if (!$raw) return null;

        // bersihkan & normalisasi ke format 62xxxx
        $clean = preg_replace('/[^0-9]/', '', $raw);
        if (str_starts_with($clean, '0')) {
            $clean = config('fonnte.country_code', '62') . substr($clean, 1);
        } elseif (!str_starts_with($clean, config('fonnte.country_code', '62'))) {
            $clean = config('fonnte.country_code', '62') . $clean;
        }
        return $clean;
    }

    /**
     * Generate fresh link token untuk telegram self-connect.
     * Token expire kalau user re-generate atau setelah linked.
     */
    public function generateTelegramLinkToken(): string
    {
        $token = 'tk_' . substr(bin2hex(random_bytes(16)), 0, 16);
        $this->forceFill(['telegram_link_token' => $token])->save();
        return $token;
    }

    /** Sudah link Telegram apa belum */
    public function hasTelegramLinked(): bool
    {
        return !empty($this->telegram_chat_id);
    }
}
