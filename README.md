# 🏸 Badminton Booking

Sistem booking lapangan badminton online. Dibangun dengan Laravel 12, Inertia.js, Vue 3, dan Tailwind CSS.

## Tech Stack

| Layer      | Technology                         |
|------------|------------------------------------|
| Backend    | Laravel 12 (PHP 8.4)               |
| Real-Time  | Laravel Reverb + Laravel Echo      |
| Frontend   | Vue 3 + Inertia.js 2               |
| Styling    | Tailwind CSS 4                     |
| Build Tool | Vite 6                             |
| Auth       | Laravel Breeze + Sanctum           |
| RBAC       | Spatie Laravel Permission           |
| Database   | MySQL 8                            |

## Prerequisites

- PHP >= 8.2
- Composer >= 2.x
- Node.js >= 20.x
- MySQL >= 8.0
- Git

## Instalasi

### 1. Clone repository

```bash
git clone <repository-url> badminton-booking
cd badminton-booking
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Setup environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit file `.env` dan sesuaikan konfigurasi database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=badminton_booking
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Buat database

Buat database `badminton_booking` di MySQL (Laragon: buka HeidiSQL → klik kanan → Create new → Database).

### 5. Jalankan migration & seeder

```bash
php artisan migrate --seed
```

### 6. Jalankan aplikasi

```bash
# Terminal 1: Backend
php artisan serve

# Terminal 2: Frontend (Vite dev server)
npm run dev

# Terminal 3: WebSocket Server (Real-Time Booking)
php artisan reverb:start
```

Buka browser: [http://localhost:8000](http://localhost:8000)

## Default Credentials

| Role   | Email                  | Password   |
|--------|------------------------|------------|
| Admin  | admin@badminton.test   | password   |
| User 1 | user@badminton.test    | password   |
| User 2 | user2@badminton.test   | password   |

## Struktur Folder Frontend

```
resources/js/
├── Components/     # Reusable Vue components
├── Layouts/        # Layout templates (AuthenticatedLayout, AdminLayout, GuestLayout)
├── Pages/          # Inertia pages (route-based)
│   ├── Admin/      # Courts CRUD Admin
│   ├── Auth/       # Login, Register, dll
│   ├── Bookings/   # Create Booking & My Bookings (Real-Time)
│   ├── Profile/    # Profile management
│   └── Dashboard.vue
├── echo.js         # Laravel Echo & Reverb WebSocket client
└── app.js          # Entry point
```

## Fitur Simulasi Pembayaran (Demo / Portfolio Sandbox)

Aplikasi dilengkapi dengan alur simulasi pembayaran lengkap (Virtual Account Bank & QRIS E-Wallet) tanpa memerlukan kartu kredit atau integrasi payment gateway berbayar:

1. **Alur Booking & Checkout**:
   - Setelah memilih slot dan membuat booking, booking berstatus `pending` dan otomatis membuat invoice unik berformat `INV-YYYYMMDD-XXXX`.
   - Pengguna diarahkan ke `/payments/{payment}` untuk memilih metode: **Simulasi Transfer Bank** atau **Simulasi E-Wallet**.
2. **Halaman Menunggu Konfirmasi & Dev Sandbox**:
   - Pengguna diarahkan ke `/payments/{payment}/waiting` yang menyajikan detail pembayaran (No. Rekening VA / QR Code QRIS simulasi) dan hitung mundur 15 menit.
   - Tersedia panel **"DEV TESTING SANDBOX"** yang aman dan jelas untuk simulasi:
     - **Simulasikan Pembayaran Berhasil**: Mengubah status payment menjadi `paid`, status booking menjadi `confirmed`, menetapkan `paid_at`, memicu event `PaymentCompleted`, memberikan poin loyalty reward (1 poin per Rp 10.000), serta mengirim notifikasi email (logged).
     - **Simulasikan Pembayaran Gagal**: Mengubah status payment menjadi `failed`, membatalkan booking (`cancelled`), membebaskan slot lapangan secara real-time via WebSocket Reverb, dan mengirim notifikasi email pembatalan.
3. **Official Printable Invoice**:
   - Invoice resmi berdesain modern & sporty dapat diakses dan dicetak kapan saja melalui `/payments/{payment}/invoice` atau menu **"Booking Saya"** (`/my-bookings`).
4. **Auto-Expiry Pending Bookings (15 Menit)**:
   - Command terjadwal `bookings:expire-pending` otomatis membatalkan booking berstatus `pending` yang belum dibayar lebih dari 15 menit.

```bash
# Menjalankan manual pemeriksaan & pembatalan booking pending kadaluarsa
php artisan bookings:expire-pending

# Menjalankan scheduler Laravel di local
php artisan schedule:work
```

## Development Commands

```bash
# Jalankan dev server (hot reload)
npm run dev

# Jalankan WebSocket server Reverb
php artisan reverb:start

# Jalankan scheduler untuk auto-cancel pending bookings
php artisan schedule:work

# Build untuk production
npm run build

# Jalankan tests
php artisan test

# Format code (Laravel Pint)
./vendor/bin/pint

# Fresh migration + seed
php artisan migrate:fresh --seed
```

## License

[MIT License](LICENSE)

