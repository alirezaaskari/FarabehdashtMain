<?php

declare(strict_types=1);

use App\Modules\Identity\Http\Controllers\AccountController;
use App\Modules\Identity\Http\Controllers\LoginController;
use App\Modules\Identity\Http\Controllers\ProfileController;
use App\Modules\Identity\Http\Controllers\SignOutController;
use App\Modules\Identity\Http\Controllers\VerifyCodeController;
use Illuminate\Support\Facades\Route;

/*
| نشانی مسیرهای کارکردی لاتین است. نشانی فارسی برای صفحات محتوایی نگه
| داشته می‌شود (دانشنامه و بانک مواد، بخش ۹ و ۱۰) که ارزش سئویی دارد؛
| مسیرهای ورود و پنل ارزش سئویی ندارند و لاتین‌بودنشان از دردسر
| کدگذاری در فرم، لاگ و تست جلوگیری می‌کند.
*/

Route::middleware('guest')->group(function (): void {
    // نام این یکی مسیر بدون پیشوند ماژول است: فریم‌ورک و بسته‌هایش مسیر
    // «login» را نام قراردادی صفحه ورود می‌دانند و میان‌افزار auth مهمان را
    // به همان می‌فرستد.
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('identity.login.store');

    Route::get('/login/verify', [VerifyCodeController::class, 'show'])->name('identity.verify.show');
    Route::post('/login/verify', [VerifyCodeController::class, 'store'])->name('identity.verify.store');
    Route::post('/login/resend', [VerifyCodeController::class, 'resend'])->name('identity.verify.resend');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', SignOutController::class)->name('identity.signout');

    Route::get('/workspace/account', [AccountController::class, 'show'])->name('identity.account');
    Route::put('/workspace/account', [AccountController::class, 'update'])->name('identity.account.update');
    Route::post('/workspace/account/sign-out-others', [AccountController::class, 'signOutOthers'])
        ->name('identity.account.sign-out-others');

    Route::get('/workspace/profiles', [ProfileController::class, 'index'])->name('identity.profiles');
    Route::post('/workspace/profiles/{type}/activate', [ProfileController::class, 'activate'])
        ->name('identity.profiles.activate');
    Route::post('/workspace/profiles/{type}/deactivate', [ProfileController::class, 'deactivate'])
        ->name('identity.profiles.deactivate');
});
