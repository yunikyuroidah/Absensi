<?php

use App\Http\Controllers\Api\MobileAdminController;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\MobilePublicController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function (): void {
    Route::get('/bootstrap', [MobilePublicController::class, 'bootstrap']);
    Route::post('/attendance', [MobilePublicController::class, 'storeAttendance']);

    Route::post('/admin/login', [MobileAuthController::class, 'login']);

    Route::middleware('admin.api')->prefix('admin')->group(function (): void {
        Route::post('/logout', [MobileAuthController::class, 'logout']);

        Route::get('/dashboard', [MobileAdminController::class, 'dashboard']);

        Route::get('/employees', [MobileAdminController::class, 'indexEmployees']);
        Route::post('/employees', [MobileAdminController::class, 'storeEmployee']);
        Route::put('/employees/{employee}', [MobileAdminController::class, 'updateEmployee']);
        Route::delete('/employees/{employee}', [MobileAdminController::class, 'destroyEmployee']);

        Route::get('/attendances', [MobileAdminController::class, 'indexAttendances']);
        Route::post('/attendances', [MobileAdminController::class, 'storeAttendance']);
        Route::put('/attendances/{jenis}/{record}', [MobileAdminController::class, 'updateAttendance'])
            ->whereIn('jenis', ['masuk', 'keluar']);
        Route::delete('/attendances/{jenis}/{record}', [MobileAdminController::class, 'destroyAttendance'])
            ->whereIn('jenis', ['masuk', 'keluar']);

        Route::get('/login-attempts', [MobileAdminController::class, 'indexLoginAttempts']);
    });
});
