<?php

declare(strict_types=1);

use App\Modules\Marketplace\Http\Controllers\BidController;
use App\Modules\Marketplace\Http\Controllers\ClientProjectController;
use App\Modules\Marketplace\Http\Controllers\ContractController;
use App\Modules\Marketplace\Http\Controllers\InviteController;
use App\Modules\Marketplace\Http\Controllers\MarketController;
use App\Modules\Marketplace\Http\Controllers\ProjectFileController;
use App\Modules\Marketplace\Http\Controllers\ThreadController;
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

// بازگشت درگاه بی‌نشست هم ممکن است؛ مرحله با Authority پیدا می‌شود.
Route::get('/market/milestones/callback', [ContractController::class, 'callback'])->name('market.milestones.callback');

Route::middleware('auth')->name('market.')->group(function (): void {
    Route::prefix('workspace/market/contracts')->name('contracts.')->group(function (): void {
        Route::post('/accept/{uuid}', [ContractController::class, 'accept'])->whereUuid('uuid')->name('accept');
        Route::get('/{uuid}', [ContractController::class, 'show'])->whereUuid('uuid')->name('show');
        Route::get('/files/{uuid}', [ContractController::class, 'file'])->whereUuid('uuid')->name('file');
        Route::post('/{uuid}/cancel', [ContractController::class, 'cancel'])->whereUuid('uuid')->name('cancel');
    });

    Route::prefix('workspace/market/milestones/{uuid}')->whereUuid('uuid')->name('milestones.')->group(function (): void {
        Route::post('/pay', [ContractController::class, 'pay'])->middleware('throttle:20,60')->name('pay');
        Route::post('/deliver', [ContractController::class, 'deliver'])->middleware('throttle:30,60')->name('deliver');
        Route::post('/approve', [ContractController::class, 'approve'])->name('approve');
        Route::post('/revise', [ContractController::class, 'revise'])->name('revise');
        Route::post('/dispute', [ContractController::class, 'dispute'])->middleware('throttle:10,60')->name('dispute');
        Route::post('/cancel-request', [ContractController::class, 'requestCancel'])->name('cancel.request');
        Route::post('/cancel-response', [ContractController::class, 'respondCancel'])->name('cancel.respond');
        Route::post('/cancel-overdue', [ContractController::class, 'cancelOverdue'])->name('cancel.overdue');
    });

    Route::get('/market/{project}/bid', [BidController::class, 'create'])->whereNumber('project')->name('bid.create');
    Route::post('/market/{project}/bid', [BidController::class, 'store'])->whereNumber('project')->middleware('throttle:30,60')->name('bid.store');
    Route::get('/market/invite/{provider}', [InviteController::class, 'create'])->whereNumber('provider')->name('invite.create');
    Route::post('/market/invite/{provider}', [InviteController::class, 'store'])->whereNumber('provider')->middleware('throttle:30,60')->name('invite.store');

    Route::prefix('workspace/market/bids')->name('bids.')->group(function (): void {
        Route::get('/', [BidController::class, 'mine'])->name('mine');
        Route::get('/{uuid}', [ThreadController::class, 'show'])->whereUuid('uuid')->name('show');
        Route::post('/{uuid}/messages', [ThreadController::class, 'message'])->whereUuid('uuid')->middleware('throttle:60,60')->name('message');
        Route::post('/{uuid}/withdraw', [BidController::class, 'withdraw'])->whereUuid('uuid')->name('withdraw');
    });

    Route::get('/workspace/market/files/{uuid}', [ProjectFileController::class, 'download'])->whereUuid('uuid')->name('files.download');
    Route::delete('/workspace/market/files/{uuid}', [ProjectFileController::class, 'destroy'])->whereUuid('uuid')->name('files.destroy');

    Route::prefix('workspace/market')->name('client.')->group(function (): void {
        Route::get('/', [ClientProjectController::class, 'index'])->name('index');
        Route::get('/new', [ClientProjectController::class, 'create'])->name('create');
        Route::post('/', [ClientProjectController::class, 'store'])->middleware('throttle:20,60')->name('store');
        Route::get('/{uuid}', [ClientProjectController::class, 'show'])->whereUuid('uuid')->name('show');
        Route::get('/{uuid}/edit', [ClientProjectController::class, 'edit'])->whereUuid('uuid')->name('edit');
        Route::put('/{uuid}', [ClientProjectController::class, 'update'])->whereUuid('uuid')->middleware('throttle:30,60')->name('update');
        Route::post('/{uuid}/close', [ClientProjectController::class, 'close'])->whereUuid('uuid')->name('close');
    });
});
