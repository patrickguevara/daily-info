<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/{date}', [DashboardController::class, 'show'])->name('dashboard.date');

// Keep existing routes
Route::get('old-dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('old-dashboard');

require __DIR__.'/settings.php';
