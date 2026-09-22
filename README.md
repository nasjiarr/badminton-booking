# 🏸 Smash Arena — Modern Badminton Court Booking & Management System

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-2.0-9553E9?style=for-the-badge&logo=inertia&logoColor=white)](https://inertiajs.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.5-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white)](https://vuejs.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4.0-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![Laravel Reverb](https://img.shields.io/badge/Laravel_Reverb-WebSocket-FF2D20?style=for-the-badge)](https://reverb.laravel.com)
[![PWA Ready](https://img.shields.io/badge/PWA-Installable-5A0FC8?style=for-the-badge&logo=pwa&logoColor=white)](https://web.dev/progressive-web-apps/)
[![Tests](https://img.shields.io/badge/Tests-106%20Passed-brightgreen?style=for-the-badge&logo=phpunit&logoColor=white)](#testing--quality-assurance)

**Smash Arena** is an enterprise-grade, real-time badminton court booking and arena management platform designed with an energetic, athletic dark design system. Built with **Laravel 12**, **Inertia.js v2**, **Vue 3**, **Tailwind CSS v4**, and **Laravel Reverb WebSockets**, Smash Arena solves concurrency conflicts, automates member loyalty tiers, and provides venue managers with actionable business intelligence.

---

## 📌 Table of Contents

- [Key Features](#-key-features)
- [Architecture & Technical Highlights](#-architecture--technical-highlights)
  - [1. Anti-Bentrok Concurrency (Double-Booking Prevention)](#1-anti-bentrok-concurrency-double-booking-prevention)
  - [2. Real-Time Slot Broadcasting via WebSockets](#2-real-time-slot-broadcasting-via-websockets)
  - [3. Multi-Week Recurring Booking Engine](#3-multi-week-recurring-booking-engine)
  - [4. Automated Loyalty & Dynamic Membership Tiering](#4-automated-loyalty--dynamic-membership-tiering)
  - [5. Sandbox Payment Simulation](#5-sandbox-payment-simulation)
- [Tech Stack](#-tech-stack)
- [Installation Guide](#-installation-guide)
- [Default Demo Accounts](#-default-demo-accounts)
- [Demo Data & Seeder](#-demo-data--seeder)
- [Testing & Quality Assurance](#-testing--quality-assurance)
- [PWA & Mobile Experience](#-pwa--mobile-experience)
- [Project Structure](#-project-structure)
- [License](#-license)

---

## ✨ Key Features

### 🏸 Customer Experience
- **Interactive Slot Matrix**: Select 1-hour or multi-hour court slots with real-time visual feedback (Available, Selected, Reserved, Past).
- **Smart Court Search**: Instant filtering by date, time range, court type, and price; includes smart slot suggestions when desired hours are full.
- **Multi-Week Recurring Bookings**: Schedule regular sparring or training sessions for up to 3 months with automatic conflict detection and skipping.
- **Loyalty Program & Tier Discounts**: Earn 1 point per Rp 10.000 spent. Progress through Bronze (0%), Silver (5% discount), and Gold (10% discount) with automatic checkout discount application.
- **Seamless Checkout & Invoicing**: Realistic Virtual Account & QRIS simulation with 15-minute expiration countdown and printable high-res PDF invoices.
- **Dedicated User Sidebar**: Modern sidebar navigation for swift access to active bookings, history, recurring schedules, and profile settings.

### 🛡️ Venue Admin & Operations
- **Business KPI Dashboard**: Real-time tracking of daily bookings, daily revenue, court occupancy rates, and new monthly member registrations.
- **Interactive Analytics (Chart.js)**: Visual 7-day revenue bar chart, 30-day occupancy trend line, and court utilization comparisons with custom date range filters.
- **Court Schedule Management**: Manage court pricing, operating hours (06:00 - 22:00), active status, and court metadata.
- **Exportable Reports**: Generate and download financial and booking logs in Microsoft Excel (`.xlsx`) or print-ready PDF formats.

---

## 🏗️ Architecture & Technical Highlights

### 1. Anti-Bentrok Concurrency (Double-Booking Prevention)

Online reservation systems often fail under high concurrency due to race conditions where two users attempt to reserve the same court slot at the same second. Smash Arena enforces a 3-layer anti-bentrok protection:

```
[User A Request] ───┐
                    ├─► [DB Transaction Begins]
[User B Request] ───┘         │
                              ├─► Pessimistic Lock: SELECT ... FOR UPDATE on Court
                              │   (Ensures serialized execution per court)
                              │
                              ├─► Interval Overlap Check:
                              │   WHERE court_id = :id
                              │     AND booking_date = :date
                              │     AND status != 'cancelled'
                              │     AND start_time < :requested_end
                              │     AND end_time > :requested_start
                              │
                              ├─► If overlap exists: ROLLBACK & Throw ValidationException
                              └─► If slot clear: INSERT Booking & COMMIT
```

- **Pessimistic Locking (`lockForUpdate`)**: Prevents race conditions before the booking is inserted.
- **Overlap Formula**: Mathematically proves non-intersection via `start_time < $newEnd AND end_time > $newStart`.
- **Database Transactions (`DB::transaction`)**: Guarantees all multi-slot or recurring reservations succeed atomically or roll back completely.

### 2. Real-Time Slot Broadcasting via WebSockets

Smash Arena utilizes **Laravel Reverb** (first-party WebSocket server) and **Laravel Echo** to broadcast booking events instantly without client-side polling.

- **`BookingCreated`**: Broadcasts the booked court ID, date, and time range to `courts.{id}` channel. All connected browsers immediately visually lock the slot to prevent others from attempting to book.
- **`BookingCancelled`**: Broadcasts when a booking is cancelled or expired, immediately freeing the slot in all connected browsers.

### 3. Multi-Week Recurring Booking Engine

Allows users to reserve a court on the same day and time every week (e.g. Every Tuesday 19:00 - 21:00 for 4 weeks):
- Validates schedule availability for each target week.
- **Intelligent Conflict Handling**: If week 2 is already reserved by another player, week 2 is gracefully skipped while successfully confirming weeks 1, 3, and 4.
- Parent-child relation: `RecurringBooking` model tracks the master schedule, while child `Booking` records manage individual match sessions.

### 4. Automated Loyalty & Dynamic Membership Tiering

- Integrated loyalty engine (`App\Services\MembershipService`):
  - **Bronze**: 0 – 99 points (0% discount)
  - **Silver**: 100 – 299 points (5% discount applied automatically at checkout)
  - **Gold**: 300+ points (10% discount applied automatically at checkout)
- Points are awarded upon successful payment (`floor($paid_amount / 10000)`).
- Automatic tier upgrade triggers in real-time as accumulated points reach new milestones.

### 5. Sandbox Payment Simulation

> **Note on Payment Gateway**: This application includes a built-in sandbox payment simulator (Virtual Account & QRIS) designed for frictionless portfolio testing without requiring live payment credentials.

- **Sandbox Test Actions**:
  - **Simulate Success**: Confirms booking, transitions status to `paid`, generates official invoice number (`INV-YYYYMMDD-XXXX`), and credits loyalty points.
  - **Simulate Failure**: Cancels booking, releases the reserved slot back to the public grid via WebSocket broadcast.
- **Auto-Expiry Worker**: Background command `bookings:expire-pending` automatically cancels unconfirmed reservations exceeding the 15-minute payment window.

---

## 💻 Tech Stack

| Component | Technology | Version | Purpose |
| :--- | :--- | :--- | :--- |
| **Backend Framework** | Laravel | 12.x | REST API, Eloquent ORM, DB Transactions, Queue |
| **Language Runtime** | PHP | 8.4 / 8.2+ | Modern object-oriented backend runtime |
| **Frontend Framework**| Vue.js (Composition API) | 3.5.x | Reactive UI components & state management |
| **Fullstack Glue** | Inertia.js | 2.0.x | Modern monolith SPA architecture without API bloat |
| **WebSocket Server** | Laravel Reverb | 1.x | Real-time bi-directional event broadcasting |
| **CSS & Design System**| Tailwind CSS | 4.0.x | Smash Arena athletic high-contrast token theme |
| **Build Tool** | Vite | 6.x | Fast HMR and production bundle optimization |
| **PWA Engine** | vite-plugin-pwa | 1.3.x | Service worker caching & manifest generation |
| **Authorization** | Spatie Permission | 6.x | Role-Based Access Control (Admin vs Customer) |
| **Database** | MySQL | 8.0+ | Relational storage with foreign keys & indexes |
| **Testing** | PHPUnit | 11.x | Feature & Unit test coverage |

---

## 🚀 Installation Guide

### Prerequisites
- **PHP** >= 8.2 (with `pdo_mysql`, `mbstring`, `bcmath`, `fileinfo`)
- **Composer** >= 2.x
- **Node.js** >= 20.x & **npm** >= 10.x
- **MySQL** >= 8.0 or MariaDB (via Laragon, XAMPP, or Docker)

### Step-by-Step Setup

```bash
# 1. Clone the repository
git clone https://github.com/nasjiarr/badminton-booking.git
cd badminton-booking

# 2. Install backend dependencies
composer install

# 3. Install frontend dependencies
npm install

# 4. Configure environment
cp .env.example .env
php artisan key:generate

# 5. Create database
# In MySQL: CREATE DATABASE badminton_booking;
# Ensure DB credentials in .env match your local MySQL configuration:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=badminton_booking
# DB_USERNAME=root
# DB_PASSWORD=

# 6. Run migrations & rich demo seeder (100+ bookings)
php artisan migrate:fresh --seed

# 7. Start the application servers
```

### Running Local Development Servers

For full real-time functionality, open **3 terminal windows**:

```bash
# Terminal 1: Laravel Web Server
php artisan serve

# Terminal 2: Vite Dev Server (Hot Module Replacement)
npm run dev

# Terminal 3: Laravel Reverb WebSocket Server (Real-Time Slot Sync)
php artisan reverb:start
```

Visit the application at: **[http://localhost:8000](http://localhost:8000)**

---

## 👥 Default Demo Accounts

The database seeder pre-populates the following accounts for immediate testing:

| Role | Email | Password | Details |
| :--- | :--- | :--- | :--- |
| **Venue Administrator** | `admin@badminton.test` | `password` | Access to admin dashboard, charts, court CRUD, and reports |
| **Customer (Gold Member)** | `fajar@badminton.test` | `password` | 380 Points, Gold Tier (10% booking discount) |
| **Customer (Silver Member)**| `kevin@badminton.test` | `password` | 290 Points, Silver Tier (5% booking discount) |
| **Customer (Bronze Member)**| `user@badminton.test` | `password` | Standard member account with active booking history |

---

## 📊 Demo Data & Seeder

The system includes `DemoBookingSeeder.php` which generates a rich dataset to showcase live charts and metrics:
- **160+ Bookings** across 4 courts covering past 30 days, today, and future dates.
- **Active Today Stats**: Multiple bookings scheduled today to showcase realistic occupancy and revenue metrics.
- **Real-Time Recurring Series**: 2 active recurring leagues with linked child booking sessions.
- **Transaction Logs**: Varied statuses (`confirmed`, `paid`, `cancelled`, `pending`) with realistic payment methods and timestamps.

To re-seed fresh demo data at any time:
```bash
php artisan migrate:fresh --seed
```

---

## 🧪 Testing & Quality Assurance

Smash Arena maintains a comprehensive test suite covering critical transaction paths, mathematical calculation services, and security boundaries.

```bash
# Run all tests
php artisan test
```

### Test Suite Summary:
- **Total Tests**: `106 passed`
- **Total Assertions**: `781 assertions`
- **Execution Time**: ~9.5 seconds

### Core Test Categories:
1. **Critical Flows Integration (`CriticalFlowsIntegrationTest.php`)**:
   - `anti-bentrok`: Rejects concurrent conflicting bookings with atomic integrity.
   - `auto-expiry`: Expired unconfirmed bookings automatically release slot availability.
   - `loyalty & tier upgrades`: Payment completion awards points and triggers tier advancements.
   - `recurring engine`: Accurately generates future sessions while skipping conflicting weeks.
   - `role authorization`: Admin routes strictly reject non-admin users (HTTP 403).
2. **Calculation Unit Tests (`MembershipCalculationTest.php`, `OccupancyCalculationTest.php`)**:
   - Tier boundaries, discount percentage resolution, points formula, occupancy rate rounding, and zero-court division safeguards.
3. **Real-Time Events (`BookingRealtimeEventTest.php`)**:
   - Verifies channel names and payload structures for `BookingCreated` and `BookingCancelled`.

---

## 📱 PWA & Mobile Experience

Smash Arena is optimized as an installable **Progressive Web App**:
- **Offline Shell**: Service worker caches core CSS, JS, and image assets for fast loading on poor networks.
- **Install Prompt**: Custom in-app installation banner notifying mobile and desktop users.
- **Responsive Navigation**: Adaptive mobile drawer layout and responsive grid slot matrix.
- **Touch-Friendly Controls**: Athletic high-contrast button hit targets designed for mobile courtside use.

---

## 📂 Project Structure

```
badminton-booking/
├── app/
│   ├── Events/                     # WebSocket Broadcast Events (BookingCreated, BookingCancelled)
│   ├── Http/Controllers/
│   │   ├── Admin/                  # Dashboard, Court Management, Financial Reports
│   │   ├── BookingController.php   # Anti-bentrok booking handler with DB Transactions
│   │   ├── CourtSearchController.php
│   │   ├── MembershipController.php
│   │   ├── PaymentController.php   # Sandbox payment simulation & invoice generator
│   │   └── RecurringBookingController.php
│   ├── Models/                     # Booking, Court, Payment, Membership, PointHistory
│   └── Services/                   # DashboardStatsService, MembershipService
├── database/
│   ├── migrations/                 # Atomic database migrations
│   └── seeders/                    # DemoBookingSeeder (100+ bookings dataset)
├── resources/
│   ├── js/
│   │   ├── Components/             # Reusable UI (EmptyState, LoadingSkeleton, UserSidebar)
│   │   ├── Layouts/                # AuthenticatedLayout, AdminLayout, GuestLayout
│   │   ├── Pages/                  # Inertia Vue 3 Page Components
│   │   └── echo.js                 # Laravel Reverb / Echo client initialization
│   └── views/
│       ├── app.blade.php           # Inertia root HTML template
│       └── errors/                 # Themed custom error pages (404, 403, 500)
├── routes/
│   ├── web.php                     # Application route definitions
│   └── console.php                 # Scheduled artisan commands (expire-pending)
└── tests/
    ├── Feature/                    # Critical flows, recurring bookings, auth, search
    └── Unit/                       # Calculation services (membership, occupancy)
```

---

## 📄 License

This project is open-source software licensed under the [MIT license](LICENSE).
