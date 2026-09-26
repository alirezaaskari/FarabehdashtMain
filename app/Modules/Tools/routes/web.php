<?php

declare(strict_types=1);

use App\Modules\Tools\Http\Controllers\SavedCalculationController;
use App\Modules\Tools\Http\Controllers\ToolAdvisorController;
use App\Modules\Tools\Http\Controllers\ToolController;
use App\Modules\Tools\Http\Controllers\ToolIndexController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| مسیرهای ابزارها
|--------------------------------------------------------------------------
|
| ترتیب اهمیت دارد: مسیرهای ثابت (`advisor`، `calculations`) پیش از
| `{slug}` می‌آیند، وگرنه «advisor» به‌عنوان شناسه ابزار خوانده می‌شود —
| همان اشتباهی که در بخش ۵ با مسیر توقف مشاهده‌به‌عنوان‌کاربر رخ داد.
|
*/

Route::prefix('tools')->name('tools.')->group(function (): void {

    Route::get('/', ToolIndexController::class)->name('index');

    Route::get('/advisor', ToolAdvisorController::class)->name('advisor');

    // ذخیره و سابقه محاسبه نیاز به حساب دارد؛ خود محاسبه نه.
    Route::middleware('auth')->group(function (): void {
        Route::get('/calculations', [SavedCalculationController::class, 'index'])
            ->name('calculations.index');

        Route::get('/calculations/{uuid}', [SavedCalculationController::class, 'show'])
            ->name('calculations.show');

        Route::delete('/calculations/{uuid}', [SavedCalculationController::class, 'destroy'])
            ->name('calculations.destroy');

        Route::get('/calculations/{uuid}/print', [SavedCalculationController::class, 'print'])
            ->name('calculations.print');

        Route::post('/{slug}/save', [SavedCalculationController::class, 'store'])
            ->name('calculations.store');
    });

    Route::get('/{slug}', [ToolController::class, 'show'])->name('show');
    Route::post('/{slug}', [ToolController::class, 'calculate'])->name('calculate');
    Route::post('/{slug}/preview', [ToolController::class, 'preview'])->name('preview')->middleware('throttle:60,1');
    Route::delete('/{slug}/points', [ToolController::class, 'clearPoints'])->name('points.clear');

});
