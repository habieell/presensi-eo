<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general')->index();
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        DB::table('settings')->insert([
            ['key' => 'work_start_time', 'value' => '08:00:00', 'group' => 'attendance', 'type' => 'string', 'label' => 'Jam Mulai Kerja', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'work_end_time',   'value' => '17:00:00', 'group' => 'attendance', 'type' => 'string', 'label' => 'Jam Pulang Kerja', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'office_latitude', 'value' => '-6.200000', 'group' => 'attendance', 'type' => 'string', 'label' => 'Latitude Kantor', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'office_longitude','value' => '106.816666','group' => 'attendance', 'type' => 'string', 'label' => 'Longitude Kantor', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'office_radius',   'value' => '500', 'group' => 'attendance', 'type' => 'integer', 'label' => 'Radius Kantor (meter)', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'company_name',    'value' => 'Presensi TSX', 'group' => 'general', 'type' => 'string', 'label' => 'Nama Perusahaan', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
