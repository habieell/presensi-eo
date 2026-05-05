<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log User - audit trail untuk semua perubahan data user / profil
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('action', [
                'created', 'updated', 'deleted', 'restored',
                'login', 'logout', 'failed_login',
                'password_changed', 'password_reset',
                'role_changed', 'profile_request',
                'profile_approved', 'profile_rejected',
                'face_registered', 'face_updated',
            ]);
            $table->string('field')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->boolean('notification_sent')->default(false);
            $table->timestamp('logged_at')->useCurrent();

            $table->index(['user_id', 'logged_at']);
            $table->index(['actor_id', 'logged_at']);
            $table->index('action');
            $table->index(['action', 'logged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_logs');
    }
};
