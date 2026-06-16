<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Warung;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warung = Warung::first() ?? Warung::create([
            'name' => 'Warung Berkah Jaya',
            'address' => 'Jl. Contoh Alamat No. 123, Jakarta',
            'phone' => '081234567890',
            'email' => 'berkahjaya@example.com',
            'tax_percentage' => 10,
            'is_tax_enabled' => true,
        ]);

        $defaultPassword = Hash::make('password');

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

        $this->command->info('Admin Seeder berhasil dijalankan!');
    }
}
