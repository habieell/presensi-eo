<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->timestamp('event_at');
            $table->timestamp('event_end_at')->nullable();
            $table->integer('notify_before')->default(30);
            $table->enum('category', ['meeting', 'training', 'workshop', 'seminar', 'event', 'other'])->default('event');
            $table->enum('visibility', ['public', 'participants_only', 'private'])->default('public');
            $table->string('color', 9)->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->boolean('reminder_sent')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // ==== INDEXES ====
            $table->index('event_at');
            $table->index(['event_at', 'is_active']);
            $table->index('category');
            $table->index('reminder_sent');
            $table->index(['visibility', 'event_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
