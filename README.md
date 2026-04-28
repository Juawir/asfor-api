# ASFOR API

RESTful API untuk sistem manajemen ASFOR (Asosiasi Forum) dengan fitur manajemen pengguna, laporan, tugas, dan keuangan.

## Daftar Isi
- [Fitur](#fitur)
- [Teknologi](#teknologi)
- [Instalasi](#instalasi)
- [Konfigurasi](#konfigurasi)
- [API Endpoints](#api-endpoints)
- [Format Respons](#format-respons)
- [Autentikasi](#autentikasi)
- [Database](#database)
- [Testing](#testing)

## Fitur

- ✅ **Autentikasi Token** - Menggunakan Laravel Sanctum untuk token-based authentication
- ✅ **Manajemen Pengguna** - CRUD pengguna dengan role-based access control (Admin/User)
- ✅ **Manajemen Laporan** - Buat, baca, edit, hapus laporan dengan attachment
- ✅ **Manajemen Tugas** - Kelola task dengan assignment dan priority tracking
- ✅ **Manajemen Keuangan** - Track income dan expense dengan category breakdown
- ✅ **Role & Divisi** - Kontrol akses berdasarkan divisi (Hubungan Masyarakat, IT Support, Pemrograman, Training, Bidang Usaha)
- ✅ **Respons API Konsisten** - Menggunakan ApiResponse trait untuk format respons yang seragam
- ✅ **Dokumentasi Swagger** - Integrasi Swagger UI untuk API documentation

## Teknologi

- **Framework:** Laravel 11
- **Authentication:** Laravel Sanctum
- **Database:** MySQL/MariaDB
- **ORM:** Eloquent
- **API Documentation:** Swagger/OpenAPI
- **PHP Version:** >= 8.1

## Instalasi

### Prerequisites
- PHP >= 8.1
- Composer
- MySQL/MariaDB
- Node.js (untuk frontend development)

### Langkah Instalasi

1. **Clone Repository**
```bash
git clone <repository-url>
cd asfor-api
```

2. **Install Dependencies**
```bash
composer install
npm install
```

3. **Setup Environment File**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Konfigurasi Database** di file `.env`
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=asfor_api
DB_USERNAME=root
DB_PASSWORD=
```

5. **Jalankan Migrations**
```bash
php artisan migrate
```

6. **Seed Database (Optional)**
```bash
php artisan db:seed
```

7. **Build Assets**
```bash
npm run build
```

## Konfigurasi

### Environment Variables
```env
APP_NAME=ASFOR_API
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=asfor_api
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:8000
SESSION_DOMAIN=localhost
```

## API Endpoints

### Authentication
- `POST /api/login` - Login dan dapatkan token
- `POST /api/logout` - Logout (memerlukan token)

### Profile
- `GET /api/profile` - Ambil profile pengguna
- `PUT /api/profile` - Update profile pengguna
- `PUT /api/profile/password` - Ubah password

### Users (Admin Only)
- `GET /api/users` - List semua pengguna
- `POST /api/users` - Buat pengguna baru
- `GET /api/users/{id}` - Ambil detail pengguna
- `PUT /api/users/{id}` - Update pengguna
- `DELETE /api/users/{id}` - Hapus pengguna

### Reports
- `GET /api/reports` - List laporan (filtered by division)
- `POST /api/reports` - Buat laporan baru
- `GET /api/reports/{id}` - Ambil detail laporan
- `PUT /api/reports/{id}` - Update laporan
- `DELETE /api/reports/{id}` - Hapus laporan

Query Parameters:
- `search` - Filter berdasarkan judul
- `status` - Filter berdasarkan status
- `date` - Filter berdasarkan tanggal
- `division` - Filter berdasarkan divisi (Admin only)

### Tasks
- `GET /api/tasks` - List tugas (filtered by division)
- `POST /api/tasks` - Buat tugas baru
- `GET /api/tasks/{id}` - Ambil detail tugas
- `PUT /api/tasks/{id}` - Update tugas
- `DELETE /api/tasks/{id}` - Hapus tugas

Query Parameters:
- `search` - Filter berdasarkan judul
- `status` - Filter berdasarkan status
- `priority` - Filter berdasarkan prioritas
- `assigned_to` - Filter berdasarkan user ID
- `division` - Filter berdasarkan divisi (Admin only)

### Finances (Admin & Bidang Usaha only)
- `GET /api/finances` - List keuangan
- `POST /api/finances` - Buat record keuangan
- `GET /api/finances/{id}` - Ambil detail keuangan
- `PUT /api/finances/{id}` - Update keuangan
- `DELETE /api/finances/{id}` - Hapus keuangan
- `GET /api/finances/summary` - Ambil ringkasan keuangan

Query Parameters (untuk GET finances):
- `type` - Filter income/expense
- `month` - Filter berdasarkan bulan (1-12)
- `year` - Filter berdasarkan tahun
- `category` - Filter berdasarkan kategori

Query Parameters (untuk summary):
- `year` - Tahun (default: tahun sekarang)
- `month` - Bulan (optional)

## Format Respons

Semua API responses mengikuti format standar melalui `ApiResponse` trait:

### Success Response
```json
{
  "status": "success",
  "message": "Users retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "admin",
      "division": "IT Support"
    }
  ]
}
```

### Error Response
```json
{
  "status": "error",
  "message": "Role user harus memiliki divisi.",
  "errors": null
}
```

### Status Codes
- `200` - OK (Success)
- `201` - Created (Sukses membuat resource)
- `204` - No Content (Sukses menghapus resource)
- `400` - Bad Request
- `403` - Forbidden (Unauthorized access)
- `404` - Not Found
- `422` - Unprocessable Entity (Validation error)
- `500` - Internal Server Error

## Autentikasi

API menggunakan **Token-Based Authentication** via Laravel Sanctum.

### Login Flow
1. POST ke `/api/login` dengan email dan password
2. Terima `access_token` dari respons
3. Gunakan token di header untuk request berikutnya:
```
Authorization: Bearer {access_token}
```

### Request dengan Token
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/profile
```

## Database

### Schema Tables

#### Users
- `id` - Primary key
- `name` - Nama pengguna
- `email` - Email unik
- `password` - Password terenkripsi
- `role` - admin/user
- `division` - Divisi tempat bekerja
- `email_verified_at` - Timestamp verifikasi email
- `timestamps` - created_at, updated_at

#### Reports
- `id` - Primary key
- `title` - Judul laporan
- `division` - Divisi pembuat laporan
- `date` - Tanggal laporan
- `budget` - Anggaran
- `description` - Deskripsi
- `attachment` - Path file attachment
- `status` - Status laporan
- `timestamps` - created_at, updated_at

#### Tasks
- `id` - Primary key
- `title` - Judul tugas
- `description` - Deskripsi
- `assigned_to` - User ID penerima tugas
- `assigned_by` - User ID pemberi tugas
- `division` - Divisi tugas
- `priority` - Prioritas (high/medium/low)
- `status` - Status tugas
- `timestamps` - created_at, updated_at

#### Finances
- `id` - Primary key
- `type` - income/expense
- `amount` - Jumlah uang
- `date` - Tanggal transaksi
- `category` - Kategori
- `description` - Deskripsi
- `percentage` - Persentase (optional)
- `timestamps` - created_at, updated_at

## Testing

### Unit Tests
```bash
php artisan test --filter=Unit
```

### Feature Tests
```bash
php artisan test --filter=Feature
```

### Run All Tests
```bash
php artisan test
```

### Coverage Report
```bash
php artisan test --coverage
```

## Dokumentasi API

Akses Swagger UI di:
```
http://localhost:8000/api/docs
```

File dokumentasi Swagger tersedia di:
- `public/swagger.json` - OpenAPI spec
- `resources/views/swagger.blade.php` - UI template

## Project Structure

```
asfor-api/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       ├── AuthController.php
│   │   │       ├── ProfileController.php
│   │   │       ├── UserController.php
│   │   │       ├── ReportController.php
│   │   │       ├── TaskController.php
│   │   │       └── FinanceController.php
│   │   └── Traits/
│   │       └── ApiResponse.php
│   └── Models/
│       ├── User.php
│       ├── Report.php
│       ├── Task.php
│       └── Finance.php
├── config/ - Konfigurasi aplikasi
├── database/ - Migrations & Seeders
├── routes/
│   └── api.php - API routes
├── tests/ - Unit & Feature tests
└── public/
    └── swagger.json - API documentation
```

## Kontribusi

1. Fork repository
2. Buat feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

## Lisensi

Distributed under the MIT License. See `LICENSE` file for more information.
