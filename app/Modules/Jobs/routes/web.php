<?php

declare(strict_types=1);

use App\Modules\Jobs\Actions\SubmitApplication;
use App\Modules\Jobs\Http\Controllers\ApplicationController;
use App\Modules\Jobs\Http\Controllers\CompanyController;
use App\Modules\Jobs\Http\Controllers\CompanyDocumentController;
use App\Modules\Jobs\Http\Controllers\EmployerApplicantController;
use App\Modules\Jobs\Http\Controllers\EmployerPostingController;
use App\Modules\Jobs\Http\Controllers\JobController;
use App\Modules\Jobs\Http\Controllers\PassportController;
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

// گذرنامه اشتراکی: نشانی ثابت، فقط وقتی صاحبش روشنش کرده، همیشه noindex (DEC-69).
Route::get('/passport/{token}', [PassportController::class, 'show'])->whereUuid('token')->name('jobs.passport.show');

Route::get('/companies/{slug}', [CompanyController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('jobs.companies.show');

// بازگشت درگاه بیرون از auth است؛ زرین‌پال بدون نشست کاربر هم برمی‌گردد.
Route::get('/workspace/jobs/callback', [EmployerPostingController::class, 'callback'])->name('jobs.employer.postings.callback');

Route::middleware('auth')->name('jobs.')->group(function (): void {
    Route::get('/workspace/company/documents/{uuid}', [CompanyDocumentController::class, 'download'])->whereUuid('uuid')->name('documents.download');

    Route::prefix('workspace/passport')->name('passport.')->group(function (): void {
        Route::get('/', [PassportController::class, 'edit'])->name('edit');
        Route::put('/', [PassportController::class, 'update'])->name('update');
        Route::post('/share', [PassportController::class, 'share'])->name('share');
        Route::post('/entries', [PassportController::class, 'storeEntry'])->middleware('throttle:60,60')->name('entries.store');
        Route::delete('/entries/{entry}', [PassportController::class, 'destroyEntry'])->whereNumber('entry')->name('entries.destroy');
    });

    // فرم درخواست برای هر کاربر واردشده باز است تا بی‌نقش‌ها راه فعال‌کردن کارجو را ببینند.
    Route::get('/jobs/{posting}/apply', [ApplicationController::class, 'create'])->whereNumber('posting')->name('apply.create');
    Route::post('/jobs/{posting}/apply', [ApplicationController::class, 'store'])->whereNumber('posting')->middleware('throttle:30,60')->name('apply.store');

    Route::middleware('can:'.SubmitApplication::ABILITY)->prefix('workspace/applications')->name('applications.')->group(function (): void {
        Route::get('/', [ApplicationController::class, 'index'])->name('index');
        Route::get('/{uuid}', [ApplicationController::class, 'show'])->whereUuid('uuid')->name('show');
        Route::post('/{uuid}/messages', [ApplicationController::class, 'message'])->whereUuid('uuid')->middleware('throttle:60,60')->name('message');
        Route::post('/{uuid}/consent', [ApplicationController::class, 'consent'])->whereUuid('uuid')->name('consent');
        Route::post('/{uuid}/withdraw', [ApplicationController::class, 'withdraw'])->whereUuid('uuid')->name('withdraw');
    });

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

        Route::prefix('workspace/jobs')->name('employer.applicants.')->group(function (): void {
            Route::get('/{uuid}/applicants', [EmployerApplicantController::class, 'index'])->whereUuid('uuid')->name('index');
            Route::get('/applicants/{uuid}', [EmployerApplicantController::class, 'show'])->whereUuid('uuid')->name('show');
            Route::post('/applicants/{uuid}/status', [EmployerApplicantController::class, 'status'])->whereUuid('uuid')->name('status');
            Route::post('/applicants/{uuid}/contact', [EmployerApplicantController::class, 'contact'])->whereUuid('uuid')->middleware('throttle:60,60')->name('contact');
            Route::get('/applicants/{uuid}/resume', [EmployerApplicantController::class, 'resume'])->whereUuid('uuid')->name('resume');
            Route::post('/applicants/{uuid}/messages', [EmployerApplicantController::class, 'message'])->whereUuid('uuid')->middleware('throttle:60,60')->name('message');
        });
    });
});
