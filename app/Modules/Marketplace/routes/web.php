<?php

declare(strict_types=1);

use App\Modules\Marketplace\Http\Controllers\ClientProjectController;
use App\Modules\Marketplace\Http\Controllers\MarketController;
use App\Modules\Marketplace\Http\Controllers\ProjectFileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| بازار پروژه
|--------------------------------------------------------------------------
|
| `/market` چون `/projects` مال پروژه‌های اندازه‌گیری میزکار است. صفحه‌های
| عمومی بی‌ورودند؛ خدمت و شهر نشانی ثابت دارند و پالایش به همان نشانی می‌رود.
|
*/

Route::prefix('market')->name('market.')->group(function (): void {
    Route::get('/', [MarketController::class, 'index'])->name('index');
    Route::get('/service/{service}', [MarketController::class, 'service'])->where('service', '[a-z-]+')->name('service');
    Route::get('/in/{city}', [MarketController::class, 'city'])->where('city', '[a-z-]+')->name('city');
    Route::get('/{project}', [MarketController::class, 'show'])->whereNumber('project')->name('show');
});

Route::middleware('auth')->name('market.')->group(function (): void {
    Route::get('/workspace/market/files/{uuid}', [ProjectFileController::class, 'download'])->whereUuid('uuid')->name('files.download');
    Route::delete('/workspace/market/files/{uuid}', [ProjectFileController::class, 'destroy'])->whereUuid('uuid')->name('files.destroy');

    Route::prefix('workspace/market')->name('client.')->group(function (): void {
        Route::get('/', [ClientProjectController::class, 'index'])->name('index');
        Route::get('/new', [ClientProjectController::class, 'create'])->name('create');
        Route::post('/', [ClientProjectController::class, 'store'])->middleware('throttle:20,60')->name('store');
        Route::get('/{uuid}/edit', [ClientProjectController::class, 'edit'])->whereUuid('uuid')->name('edit');
        Route::put('/{uuid}', [ClientProjectController::class, 'update'])->whereUuid('uuid')->middleware('throttle:30,60')->name('update');
        Route::post('/{uuid}/close', [ClientProjectController::class, 'close'])->whereUuid('uuid')->name('close');
    });
});
