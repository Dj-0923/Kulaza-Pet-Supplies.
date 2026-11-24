<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MailSettingsController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\StaffAccountController;
use App\Http\Controllers\StaffSetupController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));
Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
Route::get('/staff-setup/{token}', [StaffSetupController::class, 'edit'])->name('staff.setup');
Route::post('/staff-setup', [StaffSetupController::class, 'update'])->name('staff.setup.update');
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::middleware('role:admin')->group(function () {
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
        Route::put('/profile/mail-settings', [MailSettingsController::class, 'update'])->name('profile.mail-settings.update');
        Route::delete('/profile/mail-settings', [MailSettingsController::class, 'destroy'])->name('profile.mail-settings.destroy');
    });
    Route::get('/catalog', [ProductController::class, 'catalog'])->name('catalog');
    Route::get('/record-sale', [SalesController::class, 'create'])->name('sales.create');
    Route::post('/record-sale', [SalesController::class, 'store'])->name('sales.store');
    Route::get('/transactions', [SalesController::class, 'transactions'])->name('transactions');

    Route::middleware('role:admin')->group(function () {
        Route::get('/products', [ProductController::class, 'index'])->name('products');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::post('/products/{product}/stock', [ProductController::class, 'addStock'])->name('products.stock');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::delete('/categories/{category}', [ProductController::class, 'destroyCategory'])->name('categories.destroy');
        Route::delete('/brands/{brand}', [ProductController::class, 'destroyBrand'])->name('brands.destroy');
        Route::get('/sales-reports', [ReportController::class, 'sales'])->name('reports');
        Route::get('/price-records', [ReportController::class, 'priceRecords'])->name('price-records');
        Route::delete('/price-records/{priceRecord}', [ReportController::class, 'destroyPriceRecord'])->name('price-records.destroy');
        Route::delete('/transactions/{transaction}', [SalesController::class, 'destroyTransaction'])->name('transactions.destroy');
        Route::get('/staff-accounts', [StaffAccountController::class, 'index'])->name('staff');
        Route::get('/staff-accounts/statuses', [StaffAccountController::class, 'statuses'])->name('staff.statuses');
        Route::post('/staff-accounts', [StaffAccountController::class, 'store'])->name('staff.store');
        Route::patch('/staff-accounts/{user}/setup-email', [StaffAccountController::class, 'resendSetupEmail'])->name('staff.setup-email');
        Route::patch('/staff-accounts/{user}/status', [StaffAccountController::class, 'updateStatus'])->name('staff.status');
        Route::patch('/staff-accounts/{user}/password', [StaffAccountController::class, 'resetPassword'])->name('staff.password');
        Route::delete('/staff-accounts/{user}', [StaffAccountController::class, 'destroy'])->name('staff.destroy');
    });
});
