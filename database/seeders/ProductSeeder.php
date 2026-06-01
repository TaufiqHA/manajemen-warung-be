<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Warung;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan ada warung
        $warung = Warung::firstOrCreate(
            ['email' => 'berkahjaya@example.com'],
            [
                'name' => 'Warung Berkah Jaya',
                'address' => 'Jl. Contoh Alamat No. 123, Jakarta',
                'phone' => '081234567890',
                'tax_percentage' => 10,
                'is_tax_enabled' => true,
            ]
        );

        // Buat Kategori Dummy
        $category = Category::firstOrCreate(
            [
                'warung_id' => $warung->id,
                'name' => 'Makanan Ringan',
            ],
            [
                'description' => 'Berbagai macam makanan ringan',
            ]
        );

        // Buat Produk Dummy
        Product::create([
            'warung_id' => $warung->id,
            'category_id' => $category->id,
            'name' => 'Keripik Singkong',
            'description' => 'Keripik singkong gurih renyah',
            'price' => 5000,
            'stock' => 100,
            'unit' => 'pcs',
            'is_active' => true,
        ]);

        $this->command->info('1 data produk baru berhasil ditambahkan!');
    }
}
