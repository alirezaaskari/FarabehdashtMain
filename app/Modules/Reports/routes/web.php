<?php

declare(strict_types=1);

use App\Contracts\SalesSwitch;
use App\Modules\Reports\Http\Controllers\ReportController;
use App\Modules\Reports\Http\Controllers\ReportPurchaseController;
use App\Modules\Reports\Http\Controllers\ReportStepController;
use App\Modules\Reports\Http\Controllers\VerificationController;
use App\Modules\Reports\Providers\ReportsServiceProvider;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| مسیرهای گزارش‌ساز
|--------------------------------------------------------------------------
|
| گزارش‌ساز داده خصوصی کاربر است و همه‌اش پشت ورود است. تنها استثنا صفحه
| تأیید اصالت است: گیرنده گزارش حساب فرابهداشت ندارد.
|
*/

Route::name('reports.')->group(function (): void {

    Route::middleware('auth')->prefix('workspace/reports')->group(function (): void {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/new', [ReportController::class, 'create'])->name('create');
        Route::post('/', [ReportController::class, 'store'])->name('store');

        Route::get('/{uuid}', [ReportController::class, 'show'])->whereUuid('uuid')->name('show');
        Route::delete('/{uuid}', [ReportController::class, 'destroy'])->whereUuid('uuid')->name('destroy');
        Route::get('/{uuid}/pdf', [ReportController::class, 'download'])->whereUuid('uuid')->name('download');
        Route::post('/{uuid}/revise', [ReportController::class, 'revise'])->whereUuid('uuid')->name('revise');
        Route::post('/{uuid}/revoke', [ReportController::class, 'revoke'])->whereUuid('uuid')->name('revoke');

        Route::get('/{uuid}/details', [ReportStepController::class, 'details'])->whereUuid('uuid')->name('details');
        Route::put('/{uuid}/details', [ReportStepController::class, 'saveDetails'])->whereUuid('uuid')->name('details.save');
        Route::get('/{uuid}/findings', [ReportStepController::class, 'findings'])->whereUuid('uuid')->name('findings');
        Route::put('/{uuid}/findings', [ReportStepController::class, 'saveFindings'])->whereUuid('uuid')->name('findings.save');
        Route::get('/{uuid}/review', [ReportStepController::class, 'review'])->whereUuid('uuid')->name('review');
        Route::get('/{uuid}/preview', [ReportStepController::class, 'preview'])->whereUuid('uuid')->name('preview');
        Route::post('/{uuid}/issue', [ReportStepController::class, 'issue'])->whereUuid('uuid')->name('issue');

        // خرید تکی صدور (بخش ۱۸-۵): کلید «تک‌فروشی گزارش» فقط همین مسیر را می‌بندد.
        Route::post('/{uuid}/purchase', [ReportPurchaseController::class, 'store'])
            ->whereUuid('uuid')
            ->middleware(['sales:'.SalesSwitch::REPORT_SALE, 'financial'])
            ->name('purchase');
    });

    // زرین‌پال بدون نشست کاربر به این نشانی برمی‌گردد؛ عمداً بیرون از auth است.
    Route::get('/reports/purchase/callback', [ReportPurchaseController::class, 'callback'])
        ->name('purchase.callback');

    Route::middleware('throttle:'.ReportsServiceProvider::VERIFY_LIMITER)->group(function (): void {
        Route::get('/verify', [VerificationController::class, 'form'])->name('verify.form');
        Route::get('/verify/{code}', [VerificationController::class, 'show'])->name('verify.show');
    });
});
