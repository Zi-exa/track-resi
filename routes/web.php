<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\ScanHistoryController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? to_route('dashboard') : to_route('login'));

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
    Route::post('/imports/preview', [ImportController::class, 'preview'])->name('imports.preview');
    Route::post('/imports/commit', [ImportController::class, 'commit'])->name('imports.commit');
    Route::get('/returns', [ReturnController::class, 'index'])->name('returns.index');
    Route::get('/returns/{returnRecord}', [ReturnController::class, 'show'])->name('returns.show');
    Route::post('/returns/{returnRecord}/manual-action', [ReturnController::class, 'manualAction'])->name('returns.manual-action');
    Route::post('/returns/{returnRecord}/report', [ReturnController::class, 'report'])->name('returns.report');
    Route::post('/returns/{returnRecord}/lost', [ReturnController::class, 'markLost'])->name('returns.lost');
    Route::get('/inspections', [InspectionController::class, 'index'])->name('inspections.index');
    Route::get('/scan', [ScanController::class, 'index'])->name('scan.index');
    Route::post('/scan/lookup', [ScanController::class, 'lookup'])->name('scan.lookup');
    Route::post('/scan/{returnRecord}/confirm', [ScanController::class, 'confirm'])->name('scan.confirm');
    Route::post('/scan/unknown', [ScanController::class, 'unknown'])->name('scan.unknown');
    Route::get('/scan-history', [ScanHistoryController::class, 'index'])->name('scan-history.index');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export.csv', [ReportController::class, 'csv'])->name('reports.csv');
    Route::get('/reports/export.xlsx', [ReportController::class, 'xlsx'])->name('reports.xlsx');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::put('/settings/monitoring', [SettingsController::class, 'updateMonitoring'])->name('settings.monitoring');
    Route::put('/settings/system', [SettingsController::class, 'updateSystem'])->name('settings.system');
    Route::post('/settings/deadlines', [SettingsController::class, 'storeDeadline'])->name('settings.deadlines.store');
    Route::put('/settings/deadlines/{deadline}', [SettingsController::class, 'updateDeadline'])->name('settings.deadlines.update');
    Route::delete('/settings/deadlines/{deadline}', [SettingsController::class, 'destroyDeadline'])->name('settings.deadlines.destroy');
});
