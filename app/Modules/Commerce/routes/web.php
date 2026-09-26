<?php

declare(strict_types=1);

use App\Contracts\SalesSwitch;
use App\Modules\Commerce\Http\Controllers\BecomeSellerController;
use App\Modules\Commerce\Http\Controllers\CartController;
use App\Modules\Commerce\Http\Controllers\CheckoutController;
use App\Modules\Commerce\Http\Controllers\DownloadController;
use App\Modules\Commerce\Http\Controllers\MyPurchasesController;
use App\Modules\Commerce\Http\Controllers\ShopController;
use App\Modules\Commerce\Http\Controllers\VendorProductController;
use App\Modules\Commerce\Http\Controllers\VendorSalesController;
use App\Modules\Commerce\Http\Controllers\VendorSettlementController;
use Illuminate\Support\Facades\Route;

// «خریدهای من» روی پوسته میزکار، بیرون از کلید فروش فایل.
Route::get('/workspace/purchases', MyPurchasesController::class)
    ->middleware('auth')
    ->name('commerce.purchases');

// صفحه عمومی «فروشنده شوید»، برای فروشنده فایل و مدرس دوره؛ بیرون از کلید فروش.
Route::get('/sell', BecomeSellerController::class)->name('commerce.sell');

Route::prefix('commerce')->name('commerce.')->group(function (): void {
    // کلید «تک‌فروشی فایل» (بخش ۱۴): خاموشش ویترین، سبد و پرداخت را می‌بندد.
    // دانلود خریدهای قبلی، بازگشت درگاه و پنل فروشنده عمداً بیرون می‌مانند.
    Route::middleware('sales:'.SalesSwitch::FILE_SALE)->group(function (): void {
        Route::get('/', [ShopController::class, 'index'])->name('index');

        Route::get('/cart', [CartController::class, 'index'])->name('cart');
        Route::post('/cart/{product}', [CartController::class, 'add'])->name('cart.add');
        Route::delete('/cart/{product}', [CartController::class, 'remove'])->name('cart.remove');

        Route::post('/checkout', [CheckoutController::class, 'store'])
            ->middleware(['auth', 'financial'])
            ->name('checkout');
    });

    // زرین‌پال بدون نشست کاربر به این نشانی برمی‌گردد؛ عمداً بیرون از auth است.
    Route::get('/callback', [CheckoutController::class, 'callback'])->name('callback');

    Route::get('/products/{product}/download', DownloadController::class)
        ->middleware(['auth', 'signed'])
        ->name('download');

    // پنل فروشنده روی پوسته میزکار است (بخش ۱۵)، با ردیف «محصولات من» در ستون کناری.
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

        // تسویه (بخش ۱۸-۶): نوشتن‌ها در حالت «مشاهده به‌عنوان کاربر» بسته‌اند.
        Route::middleware('can:settlement.request')->prefix('settlement')->group(function (): void {
            Route::get('/', [VendorSettlementController::class, 'index'])->name('settlement');

            Route::middleware('financial')->group(function (): void {
                Route::put('/account', [VendorSettlementController::class, 'saveAccount'])->name('settlement.account');
                Route::post('/requests', [VendorSettlementController::class, 'request'])->name('settlement.request');
                Route::post('/requests/{uuid}/cancel', [VendorSettlementController::class, 'cancel'])->name('settlement.cancel');
            });
        });
    });

    // همیشه آخرین مسیر این گروه: هر نشانی تک‌بخشی باقی‌مانده را می‌گیرد.
    Route::get('/{product:slug}', [ShopController::class, 'show'])
        ->middleware('sales:'.SalesSwitch::FILE_SALE)
        ->name('show');
});
