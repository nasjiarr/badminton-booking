<?php

use App\Http\Controllers\Admin\CourtController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Customer Booking routes
    Route::get('/courts/search', [\App\Http\Controllers\CourtSearchController::class, 'index'])->name('courts.search');
    Route::get('/bookings', [BookingController::class, 'create'])->name('bookings.create');
    Route::get('/courts/{court}/availability', [BookingController::class, 'availability'])->name('courts.availability');
    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::get('/my-bookings', [BookingController::class, 'myBookings'])->name('my-bookings.index');
    Route::patch('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');

    // Payment Simulation routes
    Route::get('/payments/{payment}', [\App\Http\Controllers\PaymentController::class, 'show'])->name('payments.show');
    Route::post('/payments/{payment}/pay', [\App\Http\Controllers\PaymentController::class, 'pay'])->name('payments.pay');
    Route::get('/payments/{payment}/waiting', [\App\Http\Controllers\PaymentController::class, 'waiting'])->name('payments.waiting');
    Route::post('/payments/{payment}/simulate-success', [\App\Http\Controllers\PaymentController::class, 'simulateSuccess'])->name('payments.simulate-success');
    Route::post('/payments/{payment}/simulate-failed', [\App\Http\Controllers\PaymentController::class, 'simulateFailed'])->name('payments.simulate-failed');
    Route::get('/payments/{payment}/invoice', [\App\Http\Controllers\PaymentController::class, 'invoice'])->name('payments.invoice');
});

// Admin routes — protected by role:admin middleware
Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('courts', CourtController::class);
    });

require __DIR__.'/auth.php';
