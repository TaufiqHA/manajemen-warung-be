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

        // 4A. Buat User: ADMIN TOKO 1
        User::create([
            'warung_id' => $warung->id, // <- Kunci utama agar data yang ditampilkan sama
            'name' => 'Admin Toko Satu',
            'username' => 'admin_satu',
            'email' => 'admin1@warung.com',
            'password' => $defaultPassword,
            'phone' => '082222222221',
            'role' => 'ADMIN_TOKO',
            'is_active' => true,
        ]);

        // 4B. Buat User: ADMIN TOKO 2
        User::create([
            'warung_id' => $warung->id, // <- Tetap pakai $warung->id yang sama
            'name' => 'Admin Toko Dua',
            'username' => 'admin_dua',
            'email' => 'admin2@warung.com',
            'password' => $defaultPassword,
            'phone' => '082222222222',
            'role' => 'ADMIN_TOKO',
            'is_active' => true,
        ]);

        // 4C. Buat User: ADMIN TOKO 3
        User::create([
            'warung_id' => $warung->id, // <- Tetap pakai $warung->id yang sama
            'name' => 'Admin Toko Tiga',
            'username' => 'admin_tiga',
            'email' => 'admin3@warung.com',
            'password' => $defaultPassword,
            'phone' => '082222222223',
            'role' => 'ADMIN_TOKO',
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

        $this->command->info('User Seeder berhasil dijalankan! Password default: password');
    }
}
