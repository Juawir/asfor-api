# 🚀 ASFOR API

[![Laravel Version](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php)](https://www.php.net)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg?style=for-the-badge)](https://opensource.org/licenses/MIT)

RESTful API profesional untuk sistem manajemen **ASFOR (Asosiasi Forum)**. Dilengkapi dengan fitur manajemen pengguna, laporan kerja, tugas divisi, inventaris lab, dan pelaporan keuangan.

---

## 📑 Daftar Isi
- [Fitur Utama](#-fitur-utama)
- [Teknologi](#-teknologi)
- [Instalasi](#-instalasi)
- [Konfigurasi](#-konfigurasi)
- [API Endpoints](#-api-endpoints)
- [Format Respons](#-format-respons)
- [Struktur Proyek](#-struktur-proyek)
- [Kontribusi](#-kontribusi)

---

## ✨ Fitur Utama

- 🔐 **Autentikasi Aman** - Menggunakan Laravel Sanctum (Token-based).
- 👥 **Manajemen Pengguna** - RBAC (Admin & User) dengan pengelompokan Divisi.
- 📋 **Laporan Kerja** - Sistem pengajuan laporan dengan alur **Approve/Reject** oleh Admin.
- 🛠️ **Manajemen Tugas** - Penugasan task antar anggota dengan tracking status & prioritas.
- 📦 **Lab & Inventaris** - Pengelolaan barang di laboratorium dan penugasan PIC (Person In Charge).
- 📅 **Manajemen Event** - Penjadwalan dan pengelolaan acara asosiasi.
- 🗳️ **Pemilihan Ketua (Election)** - Pembuatan pemilihan, kandidat, dan real-time voting.
- 💰 **Manajemen Keuangan** - Pencatatan pemasukan & pengeluaran dengan ringkasan otomatis.
- 🔔 **Notifikasi Real-time** - Sistem pemberitahuan untuk setiap aktivitas penting.
- 📖 **Dokumentasi Swagger** - Dokumentasi API interaktif yang mudah digunakan.

---

## 🛠️ Teknologi

- **Backend Framework:** Laravel 12
- **Authentication:** Laravel Sanctum
- **Database:** MySQL / MariaDB
- **Documentation:** Swagger/OpenAPI
- **API Standard:** RESTful dengan JSON Response

---

## ⚙️ Instalasi

### Prerequisites
- PHP >= 8.2
- Composer
- MySQL/MariaDB
- Node.js & NPM

### Langkah-langkah
1. **Clone Repository**
   ```bash
   git clone https://github.com/Juawir/asfor-api.git
   cd asfor-api
   ```

2. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Setup Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Migrate & Seed**
   ```bash
   php artisan migrate --seed
   ```

5. **Jalankan Server**
   ```bash
   php artisan serve
   ```

---

## 🚀 API Endpoints

### 🔑 Authentication
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `POST` | `/api/login` | Login & dapatkan Bearer Token |
| `POST` | `/api/logout` | Revoke token aktif |

### 👤 Profile
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/profile` | Ambil data profil saya |
| `PUT` | `/api/profile` | Update data profil |
| `PUT` | `/api/profile/password` | Ganti password |

### 📄 Reports (Laporan)
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/reports` | List laporan (filter by division/status) |
| `POST` | `/api/reports` | Buat laporan baru (with attachment) |
| `PATCH` | `/api/reports/{id}/approve` | Setujui laporan (Admin Only) |
| `PATCH` | `/api/reports/{id}/reject` | Tolak laporan (Admin Only) |

### 🧪 Labs & Inventory
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/labs` | List laboratorium |
| `GET` | `/api/labs/{id}` | Detail lab & daftar barang |
| `POST` | `/api/labs/{id}/assign-pics` | Tugaskan PIC untuk Lab |
| `POST` | `/api/labs/{id}/items` | Tambah barang ke inventaris |

### 🗳️ Elections (Pemilihan Ketua)
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/elections` | Lihat status pemilihan aktif |
| `POST` | `/api/elections` | Buat pemilihan baru (Admin) |
| `POST` | `/api/elections/{id}/vote` | Memberikan suara / voting |
| `PATCH` | `/api/elections/{id}/end` | Akhiri pemilihan (Admin) |
| `DELETE`| `/api/elections/{id}` | Hapus riwayat pemilihan (Admin) |

### 📅 Events & Notifications
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/events` | List event mendatang |
| `GET` | `/api/notifications` | List notifikasi pengguna |
| `POST` | `/api/notifications/{id}/read` | Mark notifikasi sebagai terbaca |

---

## 📊 Format Respons

Aplikasi menggunakan trait `ApiResponse` untuk konsistensi data:

### Success (200/201)
```json
{
  "status": "success",
  "message": "Data retrieved successfully",
  "data": { ... }
}
```

### Error (4xx/5xx)
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "field_name": ["The field is required."]
  }
}
```

---

## 📂 Struktur Proyek

```text
app/
├── Http/
│   ├── Controllers/Api/   # Controller Utama API
│   ├── Requests/          # Validasi Form
│   └── Traits/            # ApiResponse Trait
├── Models/                # Eloquent Models
database/
├── migrations/            # Skema Database
└── seeders/               # Data Awal
routes/
└── api.php                # Definisi Route API
```

---

---

## 🚀 Deployment (Produksi)

Untuk melakukan deployment ke server produksi (VPS atau Shared Hosting), ikuti panduan berikut:

### 1. Persiapan Server
- Pastikan PHP >= 8.2 dan ekstensi yang diperlukan (BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML) aktif.
- Database MySQL/MariaDB sudah dibuat.

### 2. Konfigurasi Environment
- Salin `.env.production` menjadi `.env`.
- Jalankan perintah:
  ```bash
  php artisan key:generate --force
  ```
- Sesuaikan `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`.
- Atur `APP_DEBUG=false` dan `APP_URL` ke domain produksi Anda.

### 3. Optimasi Produksi
Jalankan perintah otomatis yang telah disiapkan:
```bash
composer deploy
```
Perintah ini akan melakukan:
- Install dependencies tanpa package development.
- Caching konfigurasi, route, dan view.
- Menjalankan migrasi database secara otomatis.

### 4. Folder Permissions
Pastikan folder berikut memiliki izin tulis (writable):
- `storage/`
- `bootstrap/cache/`

### 5. Symbolic Link
Jika Anda menggunakan fitur upload file, buat symbolic link untuk folder storage:
```bash
php artisan storage:link
```

---

## 🤝 Kontribusi

Kami menerima kontribusi dalam bentuk apapun!
1. Fork Project
2. Buat Branch Feature (`git checkout -b feature/AmazingFeature`)
3. Commit Changes (`git commit -m 'Add AmazingFeature'`)
4. Push to Branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

---

## 📄 Lisensi

Distributed under the **MIT License**. Lihat file `LICENSE` untuk informasi lebih lanjut.

---
<p align="center">Made with ❤️ by ASFOR Dev Team</p>
