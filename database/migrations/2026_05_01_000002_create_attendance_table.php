<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['in', 'out']);
            $table->date('date');
            $table->time('time');
            $table->string('photo_path')->nullable();
            $table->boolean('face_verified')->default(false);
            $table->float('face_score')->nullable();
            $table->string('location')->nullable();
            $table->double('latitude', 10, 6)->nullable();
            $table->double('longitude', 10, 6)->nullable();
            $table->enum('status', ['on_time', 'late', 'early_leave', 'overtime'])->default('on_time');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // ==== INDEXES (revisi DB) ====
            $table->index(['user_id', 'date']);
            $table->index(['user_id', 'date', 'type']);
            $table->index(['date', 'type']);
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};
