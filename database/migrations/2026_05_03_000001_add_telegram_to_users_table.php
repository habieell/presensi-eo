<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'telegram_chat_id')) {
                $table->string('telegram_chat_id', 50)->nullable()->after('whatsapp_notification');
            }
            if (!Schema::hasColumn('users', 'telegram_username')) {
                $table->string('telegram_username', 100)->nullable()->after('telegram_chat_id');
            }
            if (!Schema::hasColumn('users', 'telegram_notification')) {
                $table->boolean('telegram_notification')->default(true)->after('telegram_username');
            }

            $table->index('telegram_chat_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['telegram_chat_id']);
            $table->dropColumn(['telegram_chat_id', 'telegram_username', 'telegram_notification']);
        });
    }
};
