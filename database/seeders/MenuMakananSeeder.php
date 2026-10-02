<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Warung;
use Illuminate\Database\Seeder;

class MenuMakananSeeder extends Seeder
{
    /**
     * Menambahkan contoh produk dengan kategori "Makanan".
     *
     * Idempoten: aman dijalankan berulang kali (tidak membuat duplikat).
     * Jalankan dengan: php artisan db:seed --class=MenuMakananSeeder
     */
    public function run(): void
    {
        // Gunakan warung yang sudah ada, atau buat warung contoh bila belum ada.
        $warung = Warung::first() ?? Warung::create([
            'name' => 'Warung Berkah Jaya',
            'address' => 'Jl. Contoh Alamat No. 123, Jakarta',
            'phone' => '081234567890',
            'email' => 'berkahjaya@example.com',
            'tax_percentage' => 10,
            'is_tax_enabled' => true,
        ]);

        $category = Category::firstOrCreate([
            'warung_id' => $warung->id,
            'name' => 'Makanan',
        ]);

        $menus = [
            ['name' => 'Nasi Goreng', 'price' => 15000, 'unit' => 'porsi'],
            ['name' => 'Mie Goreng', 'price' => 13000, 'unit' => 'porsi'],
            ['name' => 'Ayam Geprek', 'price' => 15000, 'unit' => 'porsi'],
            ['name' => 'Sate Ayam', 'price' => 20000, 'unit' => 'porsi'],
        ];

        $created = 0;
        foreach ($menus as $menu) {
            $product = Product::firstOrCreate(
                [
                    'warung_id' => $warung->id,
                    'name' => $menu['name'],
                ],
                [
                    'category_id' => $category->id,
                    'price' => $menu['price'],
                    'stock' => 0,
                    'unit' => $menu['unit'],
                    'is_active' => true,
                ]
            );

            if ($product->wasRecentlyCreated) {
                $created++;
            }
        }

        $this->command->info(
            "{$created} produk kategori Makanan baru ditambahkan (total ".count($menus).' menu, warung: '.$warung->name.').'
        );
    }
}
