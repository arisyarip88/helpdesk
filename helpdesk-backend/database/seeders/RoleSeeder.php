<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Admin',
                'description' => 'Akses penuh ke seluruh konfigurasi sistem dan manajemen user.',
            ],
            [
                'name' => 'manager',
                'display_name' => 'Support Manager',
                'description' => 'Mengawasi kinerja agent, laporan SLA, dan pembagian tiket.',
            ],
            [
                'name' => 'agent',
                'display_name' => 'Support Agent',
                'description' => 'Merespon, menangani, dan meresolusikan tiket dari client.',
            ],
            [
                'name' => 'client',
                'display_name' => 'Client / User',
                'description' => 'Membuat tiket baru dan memantau status tiket milik sendiri.',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}