<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log Cash Flow - audit trail untuk semua perubahan alur kas
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_flow_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_flow_id')->nullable()->constrained('cash_flow')->nullOnDelete();
            $table->foreignId('actor_id')->constrained('users')->cascadeOnDelete();
            $table->enum('action', ['created', 'updated', 'deleted', 'approved', 'rejected', 'restored']);
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->enum('type', ['income', 'expense'])->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('logged_at')->useCurrent();

            $table->index(['cash_flow_id', 'logged_at']);
            $table->index(['actor_id', 'logged_at']);
            $table->index('action');
            $table->index('logged_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_flow_logs');
    }
};
