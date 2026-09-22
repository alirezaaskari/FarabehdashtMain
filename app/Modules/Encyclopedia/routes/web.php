<?php

declare(strict_types=1);

use App\Modules\Encyclopedia\Http\Controllers\ArticleController;
use App\Modules\Encyclopedia\Http\Controllers\ArticleIndexController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| مسیرهای دانشنامه
|--------------------------------------------------------------------------
|
| نسخه چاپی پیش از `{slug}` نمی‌آید چون زیرمسیر خود محتواست، نه هم‌سطحش.
| همین باعث می‌شود نشانی چاپی پایدار و قابل استناد بماند:
| /encyclopedia/<slug>/print
|
*/

Route::prefix('encyclopedia')->name('encyclopedia.')->group(function (): void {

    Route::get('/', ArticleIndexController::class)->name('index');

    Route::get('/{slug}', [ArticleController::class, 'show'])->name('show');
    Route::get('/{slug}/print', [ArticleController::class, 'print'])->name('print');

});
