<?php

declare(strict_types=1);

use App\Modules\Courses\Http\Controllers\CourseCheckoutController;
use App\Modules\Courses\Http\Controllers\LearnController;
use Illuminate\Support\Facades\Route;

Route::prefix('courses')->name('courses.')->group(function (): void {
    Route::post('/{course}/enroll', [CourseCheckoutController::class, 'store'])
        ->middleware('auth')
        ->name('enroll');

    // زرین‌پال بدون نشست کاربر به این نشانی برمی‌گردد؛ عمداً بیرون از auth است.
    Route::get('/callback', [CourseCheckoutController::class, 'callback'])->name('callback');

    Route::middleware('auth')->group(function (): void {
        Route::get('/{course}/learn', [LearnController::class, 'show'])->name('learn');
        Route::post('/{course}/sessions/{session}/complete', [LearnController::class, 'completeSession'])
            ->name('sessions.complete');
        Route::post('/{course}/exam', [LearnController::class, 'submitExam'])->name('exam.submit');
        Route::post('/{course}/review', [LearnController::class, 'submitReview'])->name('review.store');
    });
});
