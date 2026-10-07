<?php

declare(strict_types=1);

use App\Modules\Chemicals\Http\Controllers\ChemicalCompareController;
use App\Modules\Chemicals\Http\Controllers\ChemicalController;
use App\Modules\Chemicals\Http\Controllers\ChemicalErrorReportController;
use App\Modules\Chemicals\Http\Controllers\ChemicalHistoryController;
use App\Modules\Chemicals\Http\Controllers\ChemicalIndexController;
use App\Modules\Chemicals\Http\Controllers\ChemicalSourcesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| مسیرهای بانک مواد شیمیایی
|--------------------------------------------------------------------------
|
| compare و sources پیش از {slug} می‌آیند، وگرنه «compare» به‌عنوان شناسه ماده خوانده
| می‌شوند — همان قاعده‌ای که در ماژول ابزارها برای advisor رعایت شده.
|
*/

Route::prefix('chemicals')->name('chemicals.')->group(function (): void {

    Route::get('/', ChemicalIndexController::class)->name('index');

    Route::get('/compare', ChemicalCompareController::class)->name('compare');

    Route::get('/sources', ChemicalSourcesController::class)->name('sources');

    Route::get('/{slug}', [ChemicalController::class, 'show'])->name('show');

    Route::get('/{slug}/history', ChemicalHistoryController::class)->name('history');

    // سقف روزانه درون ReportSubstanceError است، نه throttle مسیر (شمارنده مشترک کاربر).
    Route::post('/{slug}/report', [ChemicalErrorReportController::class, 'store'])
        ->middleware('auth')
        ->name('report');

});
