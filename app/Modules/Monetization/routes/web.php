<?php

declare(strict_types=1);

use App\Modules\Monetization\Http\Controllers\PlansController;
use App\Modules\Monetization\Http\Controllers\SubscriptionCheckoutController;
use App\Modules\Monetization\Http\Controllers\TeamController;
use App\Modules\Monetization\Http\Controllers\TeamLibraryController;
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
    Route::get('/team/callback', [TeamController::class, 'callback'])->name('team.callback');

    Route::middleware('auth')->get('/team', [TeamController::class, 'buy'])->name('team.buy');

    Route::middleware(['auth', 'financial'])->group(function (): void {
        Route::post('/checkout/{slug}', [SubscriptionCheckoutController::class, 'store'])->name('checkout');
        Route::post('/cancel', [SubscriptionCheckoutController::class, 'cancel'])->name('cancel');
        Route::post('/team/checkout', [TeamController::class, 'store'])->name('team.checkout');
    });

});

/*
| تیم در میزکار (بخش ۱۹-۶): صاحب، اعضا و دعوت‌شده‌ها همه همین صفحه را دارند.
*/
Route::middleware('auth')->prefix('workspace/team')->name('monetization.')->group(function (): void {
    Route::get('/', [TeamController::class, 'show'])->name('team');
    Route::post('/invitations', [TeamController::class, 'invite'])->middleware('throttle:20,60')->name('team.invite');
    Route::post('/invitations/{uuid}/cancel', [TeamController::class, 'cancel'])->whereUuid('uuid')->name('team.invitations.cancel');
    Route::post('/invitations/{uuid}/accept', [TeamController::class, 'accept'])->whereUuid('uuid')->name('team.invitations.accept');
    Route::post('/invitations/{uuid}/decline', [TeamController::class, 'decline'])->whereUuid('uuid')->name('team.invitations.decline');
    Route::post('/members/{seat}/remove', [TeamController::class, 'remove'])->whereNumber('seat')->name('team.members.remove');
    Route::post('/leave', [TeamController::class, 'leave'])->name('team.leave');

    Route::get('/library', [TeamLibraryController::class, 'index'])->name('team.library');
    Route::post('/library', [TeamLibraryController::class, 'store'])->name('team.library.store');
    Route::get('/library/{uuid}', [TeamLibraryController::class, 'download'])->whereUuid('uuid')->name('team.library.download');
    Route::post('/library/{uuid}/delete', [TeamLibraryController::class, 'destroy'])->whereUuid('uuid')->name('team.library.destroy');
});
