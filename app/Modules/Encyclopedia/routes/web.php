<?php

declare(strict_types=1);

use App\Modules\Encyclopedia\Http\Controllers\ArticleController;
use App\Modules\Encyclopedia\Http\Controllers\ArticleIndexController;
use App\Modules\Encyclopedia\Http\Controllers\WriterArticleController;
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

// نویسنده دانشنامه (پروفایل «نویسنده»، توانایی content.write) روی پوسته میزکار.
Route::middleware(['auth', 'can:content.write'])
    ->prefix('workspace/writing')
    ->name('encyclopedia.writing.')
    ->group(function (): void {
        Route::get('/', [WriterArticleController::class, 'index'])->name('index');
        Route::get('/create', [WriterArticleController::class, 'create'])->name('create');
        Route::post('/', [WriterArticleController::class, 'store'])->name('store');
        Route::get('/{uuid}', [WriterArticleController::class, 'edit'])->name('edit');
        Route::put('/{uuid}', [WriterArticleController::class, 'update'])->name('update');
        Route::post('/{uuid}/submit', [WriterArticleController::class, 'submit'])->name('submit');
    });

Route::prefix('encyclopedia')->name('encyclopedia.')->group(function (): void {

    Route::get('/', ArticleIndexController::class)->name('index');

    Route::get('/{slug}', [ArticleController::class, 'show'])->name('show');
    Route::get('/{slug}/print', [ArticleController::class, 'print'])->name('print');

});
