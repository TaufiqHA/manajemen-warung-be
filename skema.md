# Skema Database Sistem Manajemen Warung Makan

Berikut adalah skema tabel-tabel utama yang digunakan dalam project ini berdasarkan file migrasi:

## 1. `warungs`
Tabel untuk menyimpan informasi warung/cabang.

| Kolom | Tipe Data | Keterangan |
| --- | --- | --- |
| `id` | BigInt | Primary Key |
| `name` | String | |
| `address` | String | Nullable |
| `phone` | String | Nullable |
| `email` | String | Nullable |
| `logo_url` | String | Nullable |
| `tax_percentage` | Decimal(5,2) | Default: 0 |
| `is_tax_enabled` | Boolean | Default: false |
| `receipt_footer` | Text | Nullable |
| `currency` | String | Default: 'IDR' |
| `created_at`, `updated_at` | Timestamp | |

## 2. `users`
Tabel untuk menyimpan data pengguna (owner, kasir, dll).

| Kolom | Tipe Data | Keterangan |
| --- | --- | --- |
| `id` | BigInt | Primary Key |
| `warung_id` | BigInt | Foreign Key (`warungs.id`), Cascade On Delete |
| `name` | String | |
| `username` | String | Unique, Nullable |
| `email` | String | Unique |
| `password` | String | |
| `phone` | String | Nullable |
| `role` | Enum | 'OWNER', 'ADMIN_TOKO', 'ADMIN_KANTOR', 'KASIR', 'KARYAWAN'. Default: 'KASIR' |
| `avatar_url` | String | Nullable |
| `is_active` | Boolean | Default: true |
| `email_verified_at` | Timestamp | Nullable |
| `remember_token` | String | |
| `created_at`, `updated_at` | Timestamp | |

## 3. `categories`
Tabel kategori produk.

| Kolom | Tipe Data | Keterangan |
| --- | --- | --- |
| `id` | BigInt | Primary Key |
| `warung_id` | BigInt | Foreign Key (`warungs.id`), Cascade On Delete |
| `name` | String | |
| `order` | Integer | Default: 0 |
| `description` | Text | Nullable |
| `icon` | String | Nullable |
| `created_at`, `updated_at` | Timestamp | |

## 4. `products`
Tabel data produk/menu.

| Kolom | Tipe Data | Keterangan |
| --- | --- | --- |
| `id` | BigInt | Primary Key |
| `warung_id` | BigInt | Foreign Key (`warungs.id`), Cascade On Delete |
| `category_id` | BigInt | Foreign Key (`categories.id`), Null On Delete |
| `name` | String | |
| `description` | Text | Nullable |
| `price` | UnsignedBigInteger | |
| `order` | Integer | Default: 0 |
| `stock` | Integer | Default: 0 |
| `unit` | String | Default: 'pcs' |
| `image_url` | String | Nullable |
| `is_active` | Boolean | Default: true |
| `created_at`, `updated_at` | Timestamp | |

## 5. `transactions`
Tabel transaksi penjualan.

| Kolom | Tipe Data | Keterangan |
| --- | --- | --- |
| `id` | BigInt | Primary Key |
| `warung_id` | BigInt | Foreign Key (`warungs.id`), Cascade On Delete |
| `cashier_id` | BigInt | Foreign Key (`users.id`), Cascade On Delete |
| `transaction_code`| String(50) | Unique |
| `customer_name` | String | Nullable |
| `total_amount` | UnsignedBigInteger | |
| `discount_amount`| UnsignedBigInteger | Default: 0 |
| `tax_amount` | UnsignedBigInteger | Default: 0 |
| `grand_total` | UnsignedBigInteger | |
| `payment_method` | Enum | 'CASH', 'TRANSFER', 'QRIS' |
| `paid_amount` | UnsignedBigInteger | |
| `change_amount` | UnsignedBigInteger | Default: 0 |
| `status` | String(50) | Default: 'PENDING' |
| `note` | Text | Nullable |
| `cancelled_at` | Timestamp | Nullable |
| `cancel_reason` | Text | Nullable |
| `created_at`, `updated_at` | Timestamp | |

## 6. `transaction_items`
Tabel detail produk dalam sebuah transaksi.

| Kolom | Tipe Data | Keterangan |
| --- | --- | --- |
| `id` | BigInt | Primary Key |
| `transaction_id` | BigInt | Foreign Key (`transactions.id`), Cascade On Delete |
| `product_id` | BigInt | Foreign Key (`products.id`), Cascade On Delete |
| `product_name` | String | |
| `unit_price` | UnsignedBigInteger | |
| `quantity` | Integer | |
| `served_qty` | Integer | Default: 0 |
| `discount` | UnsignedBigInteger | Default: 0 |
| `subtotal` | UnsignedBigInteger | |
| `catatan` | String | Nullable |
| `created_at`, `updated_at` | Timestamp | |

## 7. `expenses`
Tabel pencatatan pengeluaran.

| Kolom | Tipe Data | Keterangan |
| --- | --- | --- |
| `id` | BigInt | Primary Key |
| `warung_id` | BigInt | Foreign Key (`warungs.id`), Cascade On Delete |
| `created_by` | BigInt | Foreign Key (`users.id`), Cascade On Delete |
| `title` | String | |
| `amount` | UnsignedBigInteger | |
| `category` | Enum | 'BAHAN_BAKU', 'GAJI', 'LISTRIK', 'AIR', 'SEWA', 'PERALATAN', 'LAINNYA', 'BIAYA_OPERASIONAL' |
| `note` | Text | Nullable |
| `date` | Date | |
| `created_at`, `updated_at` | Timestamp | |

---

*Catatan: Tabel bawaan framework Laravel (seperti `cache`, `jobs`, `failed_jobs`, `job_batches`, `sessions`, `password_reset_tokens`, dan `personal_access_tokens`) tidak dijabarkan di atas karena menggunakan struktur bawaan dari Laravel standar.*
