<?php

declare(strict_types=1);

use App\Modules\Chemicals\Http\Controllers\ChemicalCompareController;
use App\Modules\Chemicals\Http\Controllers\ChemicalController;
use App\Modules\Chemicals\Http\Controllers\ChemicalIndexController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| مسیرهای بانک مواد شیمیایی
|--------------------------------------------------------------------------
|
| compare پیش از {slug} می‌آید، وگرنه «compare» به‌عنوان شناسه ماده خوانده
| می‌شود — همان قاعده‌ای که در ماژول ابزارها برای advisor رعایت شده.
|
*/

Route::prefix('chemicals')->name('chemicals.')->group(function (): void {

    Route::get('/', ChemicalIndexController::class)->name('index');

    Route::get('/compare', ChemicalCompareController::class)->name('compare');

    Route::get('/{slug}', [ChemicalController::class, 'show'])->name('show');

});
