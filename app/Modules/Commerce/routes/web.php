<?php

declare(strict_types=1);

use App\Modules\Commerce\Http\Controllers\CartController;
use App\Modules\Commerce\Http\Controllers\CheckoutController;
use App\Modules\Commerce\Http\Controllers\DownloadController;
use Illuminate\Support\Facades\Route;

Route::prefix('commerce')->name('commerce.')->group(function (): void {
    Route::get('/products/{product}/download', DownloadController::class)
        ->middleware(['auth', 'signed'])
        ->name('download');

    Route::get('/cart', [CartController::class, 'index'])->name('cart');
    Route::post('/cart/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::delete('/cart/{product}', [CartController::class, 'remove'])->name('cart.remove');

    Route::post('/checkout', [CheckoutController::class, 'store'])
        ->middleware('auth')
        ->name('checkout');

    // زرین‌پال بدون نشست کاربر به این نشانی برمی‌گردد؛ عمداً بیرون از auth است.
    Route::get('/callback', [CheckoutController::class, 'callback'])->name('callback');
});
