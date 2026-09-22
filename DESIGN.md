# 🏸 Smash Arena — Design System & Tokens

**Arah Visual**: **BOLD & ENERGIK** (Sporty, Dinamis, Arena Performance).  
Tampilan dirancang untuk memancarkan atmosfer gelanggang olahraga indoor yang kompetitif, berenergi tinggi, dan modern — bukan template SaaS generik yang datar dan membosankan.

---

## 🚫 Ciri Visual "AI-Generated" yang Dihindari

Aplikasi ini secara ketat **menolak** formula template AI umum:
- ❌ **Background krem/putih pucat** dengan aksen terracotta/oranye pudar.
- ❌ **Semua card seragam** dengan radius sama rata + shadow abu-abu tipis identik di mana-mana.
- ❌ **Label ALL CAPS** di atas setiap heading dengan pemisah titik tengah (`·`).
- ❌ **Tombol/link yang seragam** diakhiri panah `→`.
- ❌ **Font monospace** untuk label kecil tanpa fungsi teknis yang relevan.

---

## 1. 🎨 Palet Warna (Color Tokens)

Sistem warna menggunakan 5–6 warna inti berbasis atmosfer arena badminton dan kontras tinggi:

| Token Name | Hex Code | Peran & Penggunaan | Aksesibilitas (WCAG) |
|---|---|---|---|
| `arena-base` | `#0A0F1D` | Background utama gelap (kesan hall/arena indoor badminton malam hari). | Kontras dasar tinggi |
| `arena-card` | `#111A2E` | Container kartu dan panel di atas background dasar. | Kontras border `#24324F` |
| `arena-light` | `#F8FAFC` | Permukaan terang (high-contrast card untuk konten user-facing). | Background netral |
| **`volt` (Utama)** | **`#CCFF00`** | **Aksen utama**: Neon lime shuttlecock & garis lapangan. Digunakan **TERBATAS** untuk CTA primer, status "Tersedia", dan highlight kartu terpilih. | **Teks wajib `#0A0F1D`** (Rasio 14.2:1 — WCAG AAA) |
| **`courtOrange` (Kedua)** | **`#FF5500`** | **Aksen kedua**: Oranye bulu shuttlecock/grip raket. Untuk urgensi, pembatalan, dan peringatan kritis. *Jangan ditumpuk berlebihan dengan volt*. | Teks putih `#FFFFFF` (Rasio 4.8:1 — WCAG AA) |
| `courtSlate-900` | `#0F172A` | Teks judul utama & angka tebal pada permukaan terang. | WCAG AAA |
| `courtSlate-600` | `#475569` | Teks deskripsi & label sekunder. | WCAG AA |
| `courtSlate-200` | `#E2E8F0` | Garis border netral struktur card reguler. | Pemisah struktural |

### Aturan Penerapan Aksen
- **Smash Volt (`#CCFF00`)**: HANYA untuk elemen kunci (status *Tersedia*, tombol aksi konfirmasi utama, border kartu yang sedang dipilih).
- **Speed Orange (`#FF5500`)**: HANYA untuk status mendesak (peringatan slot diambil orang lain, tombol *Batalkan*).

---

## 2. 🔤 Tipografi (Typography Tokens)

Menggunakan pasangan 2 font yang dipilih dengan karakter tegas:

### Font Stack
- **Display & Numbers**: `'Barlow Condensed', sans-serif`  
  *Karakter*: Condensed, kokoh, berbobot tebal (700/800/900). Memberikan nuansa scoreboard digital gelanggang, jersey olahraga, dan ketegasan angka.
- **Body & UI**: `'Plus Jakarta Sans', sans-serif`  
  *Karakter*: Grotesk modern, geometris, sangat nyaman dibaca untuk form, deskripsi lapangan, dan label UI.

### Type Scale & Treatment
| Level | Font Family | Size | Weight | Tracking | Penggunaan |
|---|---|---|---|---|---|
| **Display H1** | Barlow Condensed | `36px - 44px` | 900 (Black) | `-0.02em` | Judul hero / nama lapangan utama |
| **Section H2/H3** | Barlow Condensed | `24px - 28px` | 800 (ExtraBold) | `-0.01em` | Header bagian (Pilih Lapangan, Pilih Tanggal, Slot) |
| **Athletic Numbers** | Barlow Condensed | `24px - 32px` | 900 (Black) | `tabular-nums` | **Harga (Rp 40.000)**, jam operasional, skor |
| **Body Standard** | Plus Jakarta Sans | `14px - 15px` | 500 (Medium) | `normal` | Deskripsi lapangan, instruksi, paragraf |
| **Sport Badge** | Barlow Condensed | `11px - 12px` | 800 (ExtraBold) | `+0.05em` | Badge status (TERPILIH, STANDAR PBSI, LIVE) |
| **Caption / Meta** | Plus Jakarta Sans | `12px` | 600 (SemiBold) | `normal` | Sub-label, catatan kaki |

---

## 3. 📐 Layout & Komponen (Component Tokens)

### Aksen Garis Lapangan (Court Lines)
- Terinspirasi dari garis batas dan garis servis lapangan badminton:
  - Sudut kemiringan badge: `transform: skewX(-8deg)` (sporty, dinamis).
  - Garis aksen sudut kartu: `court-stripe-accent` (potongan diagonal lime di pojok kanan kartu yang dipilih).

### Variasi Radius & Shadow Berdasarkan Hierarki
- **Kartu Biasa (Unselected)**:
  - `rounded-xl`, border solid `border-courtSlate-200`, transisi halus `hover:-translate-y-0.5 hover:shadow-card-elevated`.
- **Kartu Aktif (Selected)**:
  - `rounded-xl`, border tebal `border-volt`, ring `ring-2 ring-volt/60`, elevasi dinamis `-translate-y-1`, dan efek glow **`shadow-volt-glow`** (`rgba(204, 255, 0, 0.35)`).
- **Badge Status**:
  - `rounded-[3px]` dengan kemiringan `-8deg`, bukan kapsul bulat monoton (pill) yang generik.

### Desain State Slot Jam
- **Tersedia**: Emerald terang bergaris rapi (`bg-emerald-50 text-emerald-800 border-emerald-200`).
- **Dipilih**: Aksen volt/dark tegas yang sangat kontras.
- **Milik Anda**: Biru elektrik sporty (`bg-blue-100 text-blue-800 border-blue-200`).
- **Terisi / Booked**: Abu-abu netral teredam (`bg-gray-100 text-gray-400 cursor-not-allowed`).

---

## 4. 🚀 File Lokasi Token

- **Tailwind Extension**: [`tailwind.config.js`](file:///c:/laragon/www/badminton-booking/tailwind.config.js) (`colors.arena`, `colors.volt`, `colors.courtOrange`, `colors.courtSlate`, `fontFamily.display`, `shadows`).
- **CSS Custom Variables**: [`resources/css/design-tokens.css`](file:///c:/laragon/www/badminton-booking/resources/css/design-tokens.css).
- **Web Font Import**: [`resources/views/app.blade.php`](file:///c:/laragon/www/badminton-booking/resources/views/app.blade.php) (Bunny Fonts: `Plus Jakarta Sans` & `Barlow Condensed`).
- **Komponen Percontohan (Proof of Concept)**: Card Lapangan di [`resources/js/Pages/Bookings/Create.vue`](file:///c:/laragon/www/badminton-booking/resources/js/Pages/Bookings/Create.vue).

---

## 5. 📋 Rencana Tahap Berikutnya (Refactor Terkontrol)

Refactor halaman lain akan dieksekusi secara terpisah pada langkah berikutnya:
1. **Tahap 0.5b**: Refactor Step 2 & 3 di `Create.vue` (Date picker bergaya sporty calendar & grid slot jam interaktif).
2. **Tahap 0.5c**: Refactor halaman riwayat "Booking Saya" (`MyBookings.vue`) dengan kartu riwayat bergaya scoreboard.
3. **Tahap 0.5d**: Refactor `AdminLayout.vue` dan halaman CRUD Lapangan Admin.

