<?php

declare(strict_types=1);

use App\Modules\Courses\Http\Controllers\CourseCheckoutController;
use Illuminate\Support\Facades\Route;

Route::prefix('courses')->name('courses.')->group(function (): void {
    Route::post('/{course}/enroll', [CourseCheckoutController::class, 'store'])
        ->middleware('auth')
        ->name('enroll');

    // زرین‌پال بدون نشست کاربر به این نشانی برمی‌گردد؛ عمداً بیرون از auth است.
    Route::get('/callback', [CourseCheckoutController::class, 'callback'])->name('callback');
});
