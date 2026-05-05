<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pembicara (Speaker) - One-to-Many dengan Event
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_speakers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('calendar_events')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('title')->nullable(); // gelar/jabatan
            $table->string('organization')->nullable();
            $table->string('topic')->nullable(); // judul materi
            $table->text('bio')->nullable();
            $table->string('photo_path')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['event_id', 'order']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_speakers');
    }
};
