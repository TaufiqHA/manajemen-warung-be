<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Warung;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Buat Data Warung Dummy
        $warung = Warung::create([
            'name' => 'Warung Berkah Jaya',
            'address' => 'Jl. Contoh Alamat No. 123, Jakarta',
            'phone' => '081234567890',
            'email' => 'berkahjaya@example.com',
            'tax_percentage' => 10,
            'is_tax_enabled' => true,
        ]);

        // 2. Siapkan Password Default (Misalnya: password123)
        $defaultPassword = Hash::make('password');

        // 3. Buat User: OWNER
        User::create([
            'warung_id' => $warung->id,
            'name' => 'Budi Owner',
            'username' => 'toko_owner',
            'email' => 'owner@warung.com',
            'password' => $defaultPassword,
            'phone' => '081111111111',
            'role' => 'OWNER',
            'is_active' => true,
        ]);

        // 4. Buat User: ADMIN
        User::create([
            'warung_id' => $warung->id,
            'name' => 'Siti Admin',
            'username' => 'admin_warung',
            'email' => 'admin@warung.com',
            'password' => $defaultPassword,
            'phone' => '082222222222',
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        // 5. Buat User: ADMIN KANTOR
        User::create([
            'warung_id' => $warung->id,
            'name' => 'Andi Admin Kantor',
            'username' => 'adminkantor',
            'email' => 'adminkantor@warung.com',
            'password' => $defaultPassword,
            'phone' => '083333333333',
            'role' => 'ADMIN_KANTOR',
            'is_active' => true,
        ]);

        $this->command->info('User Seeder berhasil dijalankan! Password default: password123');
    }
}
