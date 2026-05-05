<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Captcha Challenges - simpan kode captcha yg dikirim ke client
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('captcha_challenges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 16);
            $table->string('ip_address', 45)->nullable();
            $table->boolean('used')->default(false);
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index('expires_at');
            $table->index(['ip_address', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('captcha_challenges');
    }
};
