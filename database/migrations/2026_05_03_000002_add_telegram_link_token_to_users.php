<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'telegram_link_token')) {
                $table->string('telegram_link_token', 50)->nullable()->unique()->after('telegram_notification');
            }
            if (!Schema::hasColumn('users', 'telegram_linked_at')) {
                $table->timestamp('telegram_linked_at')->nullable()->after('telegram_link_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'telegram_linked_at')) $table->dropColumn('telegram_linked_at');
            if (Schema::hasColumn('users', 'telegram_link_token')) $table->dropColumn('telegram_link_token');
        });
    }
};
