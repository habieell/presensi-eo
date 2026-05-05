<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin default
        User::updateOrCreate(
            ['email' => 'admin@presensi.test'],
            [
                'name'                  => 'Super Admin',
                'nik'                   => 'ADM-0001',
                'position'              => 'Administrator',
                'phone'                 => '081234567890',
                'whatsapp_number'       => '6281234567890',
                'telegram_chat_id'      => env('TELEGRAM_ADMIN_CHAT_IDS') ? explode(',', env('TELEGRAM_ADMIN_CHAT_IDS'))[0] : null,
                'telegram_notification' => true,
                'role'                  => 'admin',
                'is_active'             => true,
                'is_confirmed'          => true,
                'password'              => Hash::make('admin123'),
            ]
        );

        // Demo user
        User::updateOrCreate(
            ['email' => 'user@presensi.test'],
            [
                'name'             => 'Budi Santoso',
                'nik'              => 'USR-0001',
                'position'         => 'Staff',
                'phone'            => '081298765432',
                'whatsapp_number'  => '6281298765432',
                'role'             => 'user',
                'is_active'        => true,
                'is_confirmed'     => true,
                'password'         => Hash::make('user123'),
            ]
        );

        $this->command->info('✓ User seeded:');
        $this->command->line('  • admin@presensi.test / admin123');
        $this->command->line('  • user@presensi.test / user123');
    }
}
