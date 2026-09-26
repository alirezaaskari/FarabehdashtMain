<?php

declare(strict_types=1);

use App\Contracts\SalesSwitch;
use App\Modules\ExamPrep\Http\Controllers\AttemptController;
use App\Modules\ExamPrep\Http\Controllers\MyExamsController;
use App\Modules\ExamPrep\Http\Controllers\PackController;
use App\Modules\ExamPrep\Http\Controllers\PackPurchaseController;
use App\Modules\ExamPrep\Http\Controllers\QuestionWriterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| آمادگی آزمون
|--------------------------------------------------------------------------
|
| معرفی بسته‌ها عمومی است؛ نمونه، تمرین و آزمون پشت ورود. کلید «بسته‌های
| آزمون» فقط مسیر خرید را می‌بندد.
|
*/

Route::name('exam_prep.')->group(function (): void {
    Route::get('/exam-prep', [PackController::class, 'index'])->name('index');

    // زرین‌پال بدون نشست کاربر به این نشانی برمی‌گردد؛ عمداً بیرون از auth است.
    Route::get('/exam-prep/purchase/callback', [PackPurchaseController::class, 'callback'])->name('purchase.callback');

    Route::get('/exam-prep/{pack:slug}', [PackController::class, 'show'])->name('show');

    Route::middleware('auth')->group(function (): void {
        Route::post('/exam-prep/{pack:slug}/purchase', [PackPurchaseController::class, 'store'])
            ->middleware(['sales:'.SalesSwitch::EXAM_PACK, 'financial'])
            ->name('purchase');

        Route::post('/exam-prep/{pack:slug}/start', [AttemptController::class, 'start'])->name('start');

        Route::prefix('workspace/exams')->group(function (): void {
            Route::get('/', [MyExamsController::class, 'index'])->name('mine');

            Route::middleware('can:courses.manage')->prefix('questions')->group(function (): void {
                Route::get('/', [QuestionWriterController::class, 'index'])->name('writer.index');
                Route::get('/new', [QuestionWriterController::class, 'create'])->name('writer.create');
                Route::post('/', [QuestionWriterController::class, 'store'])->name('writer.store');
            });

            Route::get('/{attempt:uuid}', [AttemptController::class, 'show'])->whereUuid('attempt')->name('attempt');
            Route::post('/{attempt:uuid}/answer', [AttemptController::class, 'answer'])->whereUuid('attempt')->name('answer');
            Route::post('/{attempt:uuid}/submit', [AttemptController::class, 'submit'])->whereUuid('attempt')->name('submit');
            Route::get('/{attempt:uuid}/result', [AttemptController::class, 'result'])->whereUuid('attempt')->name('result');
        });
    });
});
