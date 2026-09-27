<?php

declare(strict_types=1);

use App\Modules\Consulting\Http\Controllers\ConsultantController;
use App\Modules\Consulting\Http\Controllers\ConsultantDocumentController;
use App\Modules\Consulting\Http\Controllers\ConsultantProfileController;
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
