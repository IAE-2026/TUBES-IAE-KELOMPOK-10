<?php

use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\AttendanceTugas3Controller;
use App\Http\Controllers\API\CentralAuthController;
use App\Http\Middleware\ApiKeyMiddleware;
use App\Http\Middleware\CentralJwtMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/sso/login', [CentralAuthController::class, 'login']);
});

// ── Main attendance routes (protected by X-IAE-KEY) ──────────────────────────
Route::middleware([ApiKeyMiddleware::class])->prefix('v1')->group(function () {
    // GET all attendances — HR Admin audit akhir bulan sebelum payroll
    Route::get('/attendances', [AttendanceController::class, 'index']);

    // GET summary per karyawan per bulan — dipanggil Payroll Service otomatis
    // HARUS di atas /{start_date}/{end_date} agar tidak tertimpa wildcard
    Route::get('/attendances/summary/{employeeId}/{year}/{month}',
               [AttendanceController::class, 'getMonthlySummary']);

    // GET by date range — HR Admin cek detail absensi satu periode
    Route::get('/attendances/{start_date}/{end_date}',
               [AttendanceController::class, 'showByDateRange']);

    // POST record absensi harian — dengan SSO + JWT dan validasi ke Employee Service
    Route::middleware([CentralJwtMiddleware::class])->post('/attendances', [AttendanceTugas3Controller::class, 'store']);
});
