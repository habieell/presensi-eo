<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['income', 'expense']);
            $table->string('icon')->nullable();
            $table->string('color', 9)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->unique(['name', 'type']);
        });

        DB::table('cash_categories')->insert([
            // Pemasukan
            ['name' => 'Iuran Anggota',   'type' => 'income',  'icon' => 'users',          'color' => '#10b981', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Donasi',          'type' => 'income',  'icon' => 'gift',           'color' => '#06b6d4', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sponsor',         'type' => 'income',  'icon' => 'briefcase',      'color' => '#8b5cf6', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Penjualan',       'type' => 'income',  'icon' => 'shopping-cart',  'color' => '#22c55e', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Lain-lain',       'type' => 'income',  'icon' => 'plus-circle',    'color' => '#84cc16', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            // Pengeluaran
            ['name' => 'Operasional',     'type' => 'expense', 'icon' => 'settings',       'color' => '#f97316', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Konsumsi',        'type' => 'expense', 'icon' => 'coffee',         'color' => '#ef4444', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Transportasi',    'type' => 'expense', 'icon' => 'truck',          'color' => '#f59e0b', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Peralatan',       'type' => 'expense', 'icon' => 'tool',           'color' => '#dc2626', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Acara/Event',     'type' => 'expense', 'icon' => 'calendar',       'color' => '#e11d48', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Lain-lain',       'type' => 'expense', 'icon' => 'minus-circle',   'color' => '#be123c', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_categories');
    }
};
