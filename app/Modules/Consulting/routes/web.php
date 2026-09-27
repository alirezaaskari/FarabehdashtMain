<?php

declare(strict_types=1);

use App\Modules\Consulting\Http\Controllers\ConsultantController;
use App\Modules\Consulting\Http\Controllers\ConsultantDocumentController;
use App\Modules\Consulting\Http\Controllers\ConsultantProfileController;
use App\Modules\Consulting\Http\Controllers\ConsultingOrderController;
use App\Modules\Consulting\Http\Controllers\ConsultingServiceController;
use App\Modules\Consulting\Http\Controllers\DirectoryContactController;
use App\Modules\Consulting\Http\Controllers\DirectoryController;
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

/*
| دایرکتوری خدمات تخصصی و صفحه آزمایشگاه (بخش ۱۹-۵). کلید خدمت و شهر از
| فهرست ثابت است؛ ناشناخته ۴۰۴ می‌شود.
*/
Route::name('consulting.')->group(function (): void {
    Route::get('/directory', [DirectoryController::class, 'index'])->name('directory.index');
    Route::get('/directory/{service}', [DirectoryController::class, 'service'])->where('service', '[a-z-]+')->name('directory.service');
    Route::get('/directory/{service}/{city}', [DirectoryController::class, 'city'])->where(['service' => '[a-z-]+', 'city' => '[a-z-]+'])->name('directory.city');

    Route::get('/labs/{slug}', [ConsultantController::class, 'lab'])->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')->name('labs.show');
    Route::post('/labs/{slug}/contact', [DirectoryContactController::class, 'store'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
        ->middleware(['auth', 'throttle:5,60'])
        ->name('contacts.store');
});

Route::middleware('auth')->name('consulting.')->group(function (): void {
    Route::get('/workspace/directory/contacts', [DirectoryContactController::class, 'mine'])->name('contacts.mine');

    Route::middleware('can:directory.contacts.manage')->prefix('workspace/lab/contacts')->group(function (): void {
        Route::get('/', [DirectoryContactController::class, 'incoming'])->name('contacts.incoming');
        Route::post('/{uuid}/reply', [DirectoryContactController::class, 'reply'])->whereUuid('uuid')->name('contacts.reply');
    });
});

Route::middleware('auth')->prefix('workspace/consultant')->name('consulting.')->group(function (): void {
    // صفحه عمومی: مشاور و آزمایشگاه (بخش ۱۹-۵) هر دو.
    Route::middleware('can:directory.listing.manage')->group(function (): void {
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
    // بررسی گزارش توسط متخصص (بخش ۱۹-۴): انتخاب بررسی‌کننده از صفحه گزارش.
    Route::get('/consulting/report-reviews', [ConsultingOrderController::class, 'pick'])->name('reviews.pick');

    Route::get('/consulting/services/{uuid}/book', [ConsultingOrderController::class, 'create'])->whereUuid('uuid')->name('orders.create');
    Route::post('/consulting/services/{uuid}/book', [ConsultingOrderController::class, 'store'])
        ->whereUuid('uuid')
        ->middleware(['financial', 'throttle:10,60'])
        ->name('orders.store');

    Route::prefix('workspace/consulting/orders')->name('orders.')->group(function (): void {
        Route::get('/', [ConsultingOrderController::class, 'mine'])->name('mine');
        Route::get('/{uuid}', [ConsultingOrderController::class, 'show'])->whereUuid('uuid')->name('show');

        foreach (['accept', 'decline', 'deliver', 'confirm', 'dispute', 'review', 'cancel'] as $step) {
            Route::post('/{uuid}/'.$step, [ConsultingOrderController::class, $step])->whereUuid('uuid')->middleware('financial')->name($step);
        }

        Route::post('/{uuid}/follow-up', [ConsultingOrderController::class, 'followUp'])->whereUuid('uuid')->name('follow-up');
        Route::post('/{uuid}/answer', [ConsultingOrderController::class, 'answer'])->whereUuid('uuid')->name('answer');

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
