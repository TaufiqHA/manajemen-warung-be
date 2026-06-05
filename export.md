# API Documentation: Ekspor Produk (Excel/CSV)

Dokumen ini menjelaskan spesifikasi API untuk fitur ekspor daftar produk. Endpoint ini digunakan untuk menghasilkan file CSV (kompatibel penuh dengan Microsoft Excel/Google Sheets) yang berisi seluruh daftar produk milik *warung* pengguna yang sedang login.

---

## 📌 Detail Endpoint

- **Method:** `GET`
- **URL:** `/api/v1/products/export`
- **Akses Role:** `OWNER`, `ADMIN_TOKO`, `ADMIN_KANTOR`
- **Tipe Autentikasi:** Bearer Token (Sanctum)

---

## 🔒 Headers

| Key | Value | Keterangan |
| :--- | :--- | :--- |
| `Authorization` | `Bearer <token>` | **Wajib**. Token autentikasi pengguna. |
| `Accept` | `application/json` | **Wajib**. Menerima response dalam format JSON. |

---

## 🔍 Query Parameters (Opsional)

Endpoint ini mendukung filter dan pengurutan (*sorting*) yang persis sama dengan endpoint *list* produk (`GET /api/v1/products`).

| Parameter | Tipe | Keterangan |
| :--- | :--- | :--- |
| `category_id` | `integer` | Filter data produk berdasarkan ID Kategori. |
| `search` | `string` | Filter data produk berdasarkan nama produk (pencarian teks). |
| `is_active` | `boolean` | `true` untuk produk aktif, `false` untuk produk tidak aktif. |
| `sort_by` | `string` | Kolom acuan pengurutan. Opsional: `name`, `price`, `stock`, `created_at`. Default: `created_at`. |
| `sort_order`| `string` | Arah pengurutan. `asc` (dari terkecil) atau `desc` (dari terbesar). Default: `desc`. |

---

## 📤 Responses

### ✅ Sukses (200 OK)

API akan menghasilkan file CSV secara fisik di *public storage* (storage server) dan mengembalikan URL publik tersebut agar Frontend dapat mengunduhnya.

```json
{
    "success": true,
    "message": "File berhasil dibuat",
    "download_url": "http://domain-api.com/storage/exports/daftar_produk_2026-06-05_13-35-15.csv"
}
```

### ❌ Gagal Autentikasi (401 Unauthorized)

Terjadi jika Bearer Token tidak valid atau tidak dikirim.

```json
{
    "message": "Unauthenticated."
}
```

### ❌ Gagal Otorisasi (403 Forbidden)

Terjadi jika *role* pengguna tidak diizinkan untuk mengekspor produk.

```json
{
    "message": "This action is unauthorized."
}
```

---

## 📝 Format Kolom File CSV Hasil Ekspor

File CSV yang dihasilkan akan dienkode menggunakan UTF-8 dengan BOM (Byte Order Mark) sehingga karakter terhindar dari *corrupt* saat dibuka langsung via Excel.

Format header kolom yang terdapat dalam file:
1. `ID Produk` (Contoh: PRD-1)
2. `Nama Produk`
3. `Kategori`
4. `Deskripsi`
5. `Harga`
6. `Stok`
7. `Satuan`
8. `Status` (Aktif / Tidak Aktif)
9. `Tanggal Dibuat` (Format YYYY-MM-DD HH:MM:SS)

---

## 💻 Contoh Penggunaan di Frontend (JavaScript)

Karena endpoint ini mengembalikan JSON berisi tautan (URL), Frontend tidak perlu memanipulasi file sebagai *Blob*. Frontend hanya perlu membuka `download_url` tersebut.

```javascript
async function exportProducts() {
    const token = 'YOUR_BEARER_TOKEN';
    
    try {
        // Contoh pemanggilan dengan parameter pencarian dan pengurutan
        const response = await fetch('/api/v1/products/export?search=Nasi&sort_by=price&sort_order=asc', {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (response.ok && data.success) {
            // Arahkan window langsung ke tautan unduhan
            // Browser secara otomatis akan memulai unduhan CSV
            window.location.href = data.download_url;
        } else {
            console.error('Gagal mengekspor:', data.message);
        }
    } catch (error) {
        console.error('Terjadi kesalahan jaringan:', error);
    }
}
```
