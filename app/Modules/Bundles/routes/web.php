<?php

declare(strict_types=1);

use App\Contracts\SalesSwitch;
use App\Modules\Bundles\Http\Controllers\BundleController;
use App\Modules\Bundles\Http\Controllers\BundlePurchaseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| بسته‌های راه‌حل
|--------------------------------------------------------------------------
|
| معرفی عمومی است؛ خرید پشت ورود. کلید «بسته‌های راه‌حل» فقط خرید را می‌بندد.
|
*/

Route::prefix('bundles')->name('bundles.')->group(function (): void {
    Route::get('/', [BundleController::class, 'index'])->name('index');

    // زرین‌پال بدون نشست کاربر به این نشانی برمی‌گردد؛ عمداً بیرون از auth است.
    Route::get('/purchase/callback', [BundlePurchaseController::class, 'callback'])->name('purchase.callback');

    Route::get('/{bundle:slug}', [BundleController::class, 'show'])->name('show');

    Route::post('/{bundle:slug}/purchase', [BundlePurchaseController::class, 'store'])
        ->middleware(['auth', 'sales:'.SalesSwitch::SOLUTION_BUNDLE, 'financial'])
        ->name('purchase');
});
