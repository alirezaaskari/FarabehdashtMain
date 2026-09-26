<?php

declare(strict_types=1);

use App\Modules\Webinars\Http\Controllers\RegistrationController;
use App\Modules\Webinars\Http\Controllers\WebinarController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| رویداد و وبینار
|--------------------------------------------------------------------------
|
| معرفی عمومی است؛ ثبت‌نام و ورود پشت ورود به حساب. کلید «ثبت‌نام رویداد»
| فقط ثبت‌نام پولی را می‌بندد؛ رویداد رایگان همیشه باز است.
|
*/

Route::prefix('webinars')->name('webinars.')->group(function (): void {
    Route::get('/', [WebinarController::class, 'index'])->name('index');

    // زرین‌پال بدون نشست کاربر به این نشانی برمی‌گردد؛ عمداً بیرون از auth است.
    Route::get('/register/callback', [RegistrationController::class, 'callback'])->name('register.callback');

    Route::get('/{webinar:slug}', [WebinarController::class, 'show'])->name('show');

    Route::middleware('auth')->group(function (): void {
        Route::post('/{webinar:slug}/register', [RegistrationController::class, 'store'])
            ->middleware('financial')
            ->name('register');

        Route::get('/{webinar:slug}/join', [WebinarController::class, 'join'])->name('join');
    });
});
