<?php

declare(strict_types=1);

use App\Modules\Commerce\Http\Controllers\CartController;
use App\Modules\Commerce\Http\Controllers\CheckoutController;
use App\Modules\Commerce\Http\Controllers\DownloadController;
use App\Modules\Commerce\Http\Controllers\ShopController;
use App\Modules\Commerce\Http\Controllers\VendorProductController;
use App\Modules\Commerce\Http\Controllers\VendorSalesController;
use App\Modules\Commerce\Http\Controllers\VendorSettlementController;
use Illuminate\Support\Facades\Route;

Route::prefix('commerce')->name('commerce.')->group(function (): void {
    Route::get('/', [ShopController::class, 'index'])->name('index');

    Route::get('/cart', [CartController::class, 'index'])->name('cart');
    Route::post('/cart/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::delete('/cart/{product}', [CartController::class, 'remove'])->name('cart.remove');

    Route::post('/checkout', [CheckoutController::class, 'store'])
        ->middleware('auth')
        ->name('checkout');

    // زرین‌پال بدون نشست کاربر به این نشانی برمی‌گردد؛ عمداً بیرون از auth است.
    Route::get('/callback', [CheckoutController::class, 'callback'])->name('callback');

    Route::get('/products/{product}/download', DownloadController::class)
        ->middleware(['auth', 'signed'])
        ->name('download');

    // پیش از یکپارچگی با میزکار مشترک (بخش ۱۵)، پنل فروشنده روی پوسته عمومی است.
    Route::middleware('auth')->prefix('vendor')->name('vendor.')->group(function (): void {
        Route::middleware('can:products.manage')->prefix('products')->name('products.')->group(function (): void {
            Route::get('/', [VendorProductController::class, 'index'])->name('index');
            Route::get('/create', [VendorProductController::class, 'create'])->name('create');
            Route::post('/', [VendorProductController::class, 'store'])->name('store');
            Route::get('/{product}', [VendorProductController::class, 'edit'])->name('edit');
            Route::post('/{product}/versions', [VendorProductController::class, 'addVersion'])->name('versions');
            Route::post('/{product}/submit', [VendorProductController::class, 'submit'])->name('submit');
            Route::post('/{product}/retire', [VendorProductController::class, 'retire'])->name('retire');
        });

        Route::get('/sales', [VendorSalesController::class, 'index'])
            ->middleware('can:sales.reports')
            ->name('sales');

        Route::get('/settlement', [VendorSettlementController::class, 'index'])
            ->middleware('can:settlement.request')
            ->name('settlement');
    });

    // همیشه آخرین مسیر این گروه: هر نشانی تک‌بخشی باقی‌مانده را می‌گیرد.
    Route::get('/{product:slug}', [ShopController::class, 'show'])->name('show');
});
