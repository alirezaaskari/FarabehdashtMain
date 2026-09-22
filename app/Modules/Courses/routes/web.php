<?php

declare(strict_types=1);

use App\Modules\Courses\Http\Controllers\CourseCatalogController;
use App\Modules\Courses\Http\Controllers\CourseCheckoutController;
use App\Modules\Courses\Http\Controllers\InstructorCourseController;
use App\Modules\Courses\Http\Controllers\InstructorSalesController;
use App\Modules\Courses\Http\Controllers\LearnController;
use Illuminate\Support\Facades\Route;

Route::prefix('courses')->name('courses.')->group(function (): void {
    Route::get('/', [CourseCatalogController::class, 'index'])->name('index');

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

    // پیش از یکپارچگی با میزکار مشترک (بخش ۱۵)، پنل مدرس روی پوسته عمومی است.
    Route::middleware('auth')->prefix('instructor')->name('instructor.')->group(function (): void {
        Route::middleware('can:courses.manage')->prefix('courses')->name('courses.')->group(function (): void {
            Route::get('/', [InstructorCourseController::class, 'index'])->name('index');
            Route::get('/create', [InstructorCourseController::class, 'create'])->name('create');
            Route::post('/', [InstructorCourseController::class, 'store'])->name('store');
            Route::get('/{course}', [InstructorCourseController::class, 'edit'])->name('edit');
            Route::post('/{course}/sessions', [InstructorCourseController::class, 'addSession'])->name('sessions');
            Route::post('/{course}/questions', [InstructorCourseController::class, 'addExamQuestion'])->name('questions');
            Route::post('/{course}/submit', [InstructorCourseController::class, 'submit'])->name('submit');
            Route::post('/{course}/retire', [InstructorCourseController::class, 'retire'])->name('retire');
        });

        Route::get('/sales', [InstructorSalesController::class, 'index'])
            ->middleware('can:sales.reports')
            ->name('sales');
    });

    // همیشه آخرین مسیر این گروه: هر نشانی تک‌بخشی باقی‌مانده را می‌گیرد.
    Route::get('/{course:slug}', [CourseCatalogController::class, 'show'])->name('show');
});
