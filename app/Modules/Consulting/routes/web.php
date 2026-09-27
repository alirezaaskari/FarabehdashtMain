<?php

declare(strict_types=1);

use App\Modules\Consulting\Http\Controllers\ConsultantController;
use App\Modules\Consulting\Http\Controllers\ConsultantDocumentController;
use App\Modules\Consulting\Http\Controllers\ConsultantProfileController;
use App\Modules\Consulting\Http\Controllers\ConsultingOrderController;
use App\Modules\Consulting\Http\Controllers\ConsultingServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| مشاوره
|--------------------------------------------------------------------------
|
| صفحه عمومی مشاوران و ویرایش صفحه در میزکار مشاور. دانلود مدرک خصوصی
| فقط برای خود مشاور و مدیر محتواست (DEC-50).
|
*/

Route::prefix('consultants')->name('consulting.')->group(function (): void {
    Route::get('/', [ConsultantController::class, 'index'])->name('index');
    Route::get('/{slug}', [ConsultantController::class, 'show'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
        ->name('show');
});

Route::middleware('auth')->prefix('workspace/consultant')->name('consulting.')->group(function (): void {
    Route::middleware('can:consulting.services.manage')->group(function (): void {
        Route::get('/profile', [ConsultantProfileController::class, 'edit'])->name('profile.edit');
        Route::post('/profile', [ConsultantProfileController::class, 'update'])->name('profile.update');
        Route::post('/documents', [ConsultantDocumentController::class, 'store'])->name('documents.store');
        Route::delete('/documents/{uuid}', [ConsultantDocumentController::class, 'destroy'])->whereUuid('uuid')->name('documents.destroy');
    });

    Route::get('/documents/{uuid}', [ConsultantDocumentController::class, 'download'])->whereUuid('uuid')->name('documents.download');
});

/*
| خرید خدمت مشاوره (بخش ۱۹-۳). بازگشت درگاه بیرون از auth است؛ زرین‌پال
| بدون نشست کاربر هم برمی‌گردد.
*/
Route::get('/consulting/orders/callback', [ConsultingOrderController::class, 'callback'])->name('consulting.orders.callback');

Route::middleware('auth')->name('consulting.')->group(function (): void {
    Route::get('/consulting/services/{uuid}/book', [ConsultingOrderController::class, 'create'])->whereUuid('uuid')->name('orders.create');
    Route::post('/consulting/services/{uuid}/book', [ConsultingOrderController::class, 'store'])
        ->whereUuid('uuid')
        ->middleware(['financial', 'throttle:10,60'])
        ->name('orders.store');

    Route::prefix('workspace/consulting/orders')->name('orders.')->group(function (): void {
        Route::get('/', [ConsultingOrderController::class, 'mine'])->name('mine');
        Route::get('/{uuid}', [ConsultingOrderController::class, 'show'])->whereUuid('uuid')->name('show');

        foreach (['accept', 'decline', 'deliver', 'confirm', 'dispute'] as $step) {
            Route::post('/{uuid}/'.$step, [ConsultingOrderController::class, $step])->whereUuid('uuid')->middleware('financial')->name($step);
        }

        Route::post('/{uuid}/messages', [ConsultingOrderController::class, 'message'])
            ->whereUuid('uuid')
            ->middleware('throttle:30,60')
            ->name('message');
    });

    Route::prefix('workspace/consultant')->middleware('can:consulting.services.manage')->group(function (): void {
        Route::get('/requests', [ConsultingOrderController::class, 'incoming'])->name('orders.incoming');
        Route::get('/services', [ConsultingServiceController::class, 'index'])->name('services.index');
        Route::get('/services/new', [ConsultingServiceController::class, 'create'])->name('services.create');
        Route::post('/services', [ConsultingServiceController::class, 'store'])->name('services.store');
        Route::get('/services/{uuid}/edit', [ConsultingServiceController::class, 'edit'])->whereUuid('uuid')->name('services.edit');
        Route::put('/services/{uuid}', [ConsultingServiceController::class, 'update'])->whereUuid('uuid')->name('services.update');
        Route::post('/services/{uuid}/retire', [ConsultingServiceController::class, 'retire'])->whereUuid('uuid')->name('services.retire');
    });
});
