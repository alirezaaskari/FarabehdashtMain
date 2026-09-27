<?php

declare(strict_types=1);

use App\Modules\Jobs\Http\Controllers\CompanyController;
use App\Modules\Jobs\Http\Controllers\CompanyDocumentController;
use App\Modules\Jobs\Http\Controllers\EmployerPostingController;
use App\Modules\Jobs\Http\Controllers\JobController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| کاریابی
|--------------------------------------------------------------------------
|
| صفحه‌های عمومی آگهی رایگان و بی‌ورودند: کارجو هرگز برای دیدن آگهی پول
| نمی‌دهد. شهر و مهارت نشانی ثابت دارند و پالایش به همان نشانی می‌رود.
|
*/

Route::prefix('jobs')->name('jobs.')->group(function (): void {
    Route::get('/', [JobController::class, 'index'])->name('index');
    Route::get('/in/{city}', [JobController::class, 'city'])->where('city', '[a-z-]+')->name('city');
    Route::get('/skill/{skill}', [JobController::class, 'skill'])->where('skill', '[a-z0-9-]+')->name('skill');
    Route::get('/{posting}', [JobController::class, 'show'])->whereNumber('posting')->name('show');
});

Route::get('/companies/{slug}', [CompanyController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('jobs.companies.show');

// بازگشت درگاه بیرون از auth است؛ زرین‌پال بدون نشست کاربر هم برمی‌گردد.
Route::get('/workspace/jobs/callback', [EmployerPostingController::class, 'callback'])->name('jobs.employer.postings.callback');

Route::middleware('auth')->name('jobs.')->group(function (): void {
    Route::get('/workspace/company/documents/{uuid}', [CompanyDocumentController::class, 'download'])->whereUuid('uuid')->name('documents.download');

    Route::middleware('can:jobs.post')->group(function (): void {
        Route::get('/workspace/company', [CompanyController::class, 'edit'])->name('company.edit');
        Route::post('/workspace/company', [CompanyController::class, 'update'])->name('company.update');
        Route::post('/workspace/company/documents', [CompanyDocumentController::class, 'store'])->name('documents.store');
        Route::delete('/workspace/company/documents/{uuid}', [CompanyDocumentController::class, 'destroy'])->whereUuid('uuid')->name('documents.destroy');

        Route::prefix('workspace/jobs')->name('employer.postings.')->group(function (): void {
            Route::get('/', [EmployerPostingController::class, 'index'])->name('index');
            Route::get('/new', [EmployerPostingController::class, 'create'])->name('create');
            Route::post('/', [EmployerPostingController::class, 'store'])->middleware('throttle:20,60')->name('store');
            Route::get('/{uuid}/edit', [EmployerPostingController::class, 'edit'])->whereUuid('uuid')->name('edit');
            Route::put('/{uuid}', [EmployerPostingController::class, 'update'])->whereUuid('uuid')->name('update');
            Route::post('/{uuid}/close', [EmployerPostingController::class, 'close'])->whereUuid('uuid')->name('close');
            Route::get('/{uuid}/publish', [EmployerPostingController::class, 'checkout'])->whereUuid('uuid')->name('checkout');
            Route::post('/{uuid}/publish', [EmployerPostingController::class, 'pay'])->whereUuid('uuid')->middleware(['financial', 'throttle:10,60'])->name('pay');
        });
    });
});
