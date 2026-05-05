<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notification Outbox - track semua notifikasi WA/Telegram yg dikirim
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_outbox', function (Blueprint $table) {
            $table->id();
            $table->enum('channel', ['whatsapp', 'telegram', 'email', 'in_app']);
            $table->string('recipient'); // nomor WA / chat_id telegram / email
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type'); // profile.updated, attendance.checkin, event.reminder
            $table->string('subject')->nullable();
            $table->text('message');
            $table->json('payload')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed', 'retry'])->default('pending');
            $table->text('response')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['channel', 'status']);
            $table->index('event_type');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_outbox');
    }
};
