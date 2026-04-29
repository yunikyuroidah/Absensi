<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LoginAttemptApiController;
use App\Http\Controllers\PublicAttendanceController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicAttendanceController::class, 'index'])->name('public.attendance.index');
Route::post('/absensi', [PublicAttendanceController::class, 'store'])->name('public.attendance.store');
Route::get('/login', fn () => redirect()->route('admin.login'))->name('login');

Route::get('/admin', function () {
    return Auth::check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('admin.login');
})->name('admin.entry');

Route::prefix('admin')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
        Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
    });

    Route::middleware('auth')->group(function (): void {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
        Route::get('/dashboard', DashboardController::class)->name('admin.dashboard');

        Route::resource('employees', EmployeeController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->names('admin.employees');

        Route::get('/attendances', [AttendanceController::class, 'index'])
            ->name('admin.attendances.index');

        Route::post('/attendances', [AttendanceController::class, 'store'])
            ->name('admin.attendances.store');

        Route::put('/attendances/{jenis}/{record}', [AttendanceController::class, 'update'])
            ->whereIn('jenis', ['masuk', 'keluar'])
            ->name('admin.attendances.update');

        Route::delete('/attendances/{jenis}/{record}', [AttendanceController::class, 'destroy'])
            ->whereIn('jenis', ['masuk', 'keluar'])
            ->name('admin.attendances.destroy');

        Route::get('/login-attempts', [LoginAttemptApiController::class, 'index'])
            ->name('admin.login-attempts.index');
    });
});
