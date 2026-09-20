<?php

declare(strict_types=1);

use App\Modules\Projects\Http\Controllers\EquipmentController;
use App\Modules\Projects\Http\Controllers\MonitoringCalendarController;
use App\Modules\Projects\Http\Controllers\ProjectComparisonController;
use App\Modules\Projects\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| مسیرهای پروژه و تجهیزات
|--------------------------------------------------------------------------
|
| همه نیازمند ورودند: پروژه و دفترچه تجهیزات داده خصوصی کاربرند.
| مسیرهای ثابت پیش از {uuid} می‌آیند.
|
*/

Route::middleware('auth')->prefix('workspace')->name('projects.')->group(function (): void {

    Route::get('/equipment', [EquipmentController::class, 'index'])->name('equipment.index');
    Route::post('/equipment', [EquipmentController::class, 'store'])->name('equipment.store');

    Route::get('/calendar', MonitoringCalendarController::class)->name('calendar');

    Route::get('/projects', [ProjectController::class, 'index'])->name('index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('store');

    Route::get('/projects/{uuid}', [ProjectController::class, 'show'])->name('show');
    Route::post('/projects/{uuid}/readings', [ProjectController::class, 'storeReading'])->name('readings.store');
    Route::post('/projects/{uuid}/acknowledge', [ProjectController::class, 'acknowledge'])->name('acknowledge');

    Route::get('/projects/{uuid}/compare', ProjectComparisonController::class)->name('compare');

});
