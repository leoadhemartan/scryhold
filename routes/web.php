<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


use App\Http\Controllers\HomeController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\SetLibraryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\Admin\CardsController as AdminCardController;
use App\Http\Controllers\Admin\LocationController as AdminLocationController;

// Home page with login prompt if not authenticated as admin
Route::get('/', [HomeController::class, 'index'])->name('home');

// Public pages
Route::get('/cards', [CardController::class, 'index'])->name('cards.index');
Route::get('/cards/{id}', [CardController::class, 'show'])->name('cards.show');
Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
Route::get('/set-library', [SetLibraryController::class, 'index'])->name('set-library.index');

// Admin-only pages (protected by auth and admin.only middleware)
Route::middleware(['auth', 'admin.only'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/admin/cards', [AdminCardController::class, 'index'])->name('admin.cards.index');
    Route::post('/admin/cards/update-set-library', [AdminCardController::class, 'updateSetLibrary'])->name('admin.cards.update-set-library');
    Route::resource('/admin/locations', AdminLocationController::class)->names([
        'index' => 'admin.locations.index',
        'create' => 'admin.locations.create',
        'store' => 'admin.locations.store',
        'show' => 'admin.locations.show',
        'edit' => 'admin.locations.edit',
        'update' => 'admin.locations.update',
        'destroy' => 'admin.locations.destroy',
    ]);
    Route::get('/password/change', [PasswordController::class, 'showChangeForm'])->name('password.change');
    Route::post('/password/change', [PasswordController::class, 'change'])->name('password.change.post');
});

// Authenticated user routes (profile management)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
