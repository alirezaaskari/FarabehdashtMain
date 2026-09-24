<?php

declare(strict_types=1);

use App\Modules\Monetization\Http\Controllers\PlansController;
use App\Modules\Monetization\Http\Controllers\SubscriptionCheckoutController;
use App\Modules\Monetization\Http\Controllers\UpgradeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| مسیرهای اشتراک
|--------------------------------------------------------------------------
|
| پیشوند `pro` است نه `monetization`: نشانی را کاربر می‌بیند و «درآمدزایی»
| اسم داخلی ماست، نه چیزی که به او مربوط باشد.
|
| ترتیب اهمیت دارد: `upgrade` و `callback` پیش از هر مسیر پویا می‌آیند.
|
*/

Route::prefix('pro')->name('monetization.')->group(function (): void {

    Route::get('/', PlansController::class)->name('plans');

    Route::get('/upgrade', UpgradeController::class)->name('upgrade');

    // زرین‌پال بدون نشست کاربر به این نشانی برمی‌گردد؛ عمداً بیرون از auth است.
    Route::get('/callback', [SubscriptionCheckoutController::class, 'callback'])->name('callback');

    Route::middleware(['auth', 'financial'])->group(function (): void {
        Route::post('/checkout/{slug}', [SubscriptionCheckoutController::class, 'store'])->name('checkout');
        Route::post('/cancel', [SubscriptionCheckoutController::class, 'cancel'])->name('cancel');
    });

});
