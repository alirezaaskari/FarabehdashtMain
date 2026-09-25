<?php

declare(strict_types=1);

use App\Modules\Expert\Http\Controllers\AnswerController;
use App\Modules\Expert\Http\Controllers\ExpertWorkspaceController;
use App\Modules\Expert\Http\Controllers\QuestionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| پرسش از متخصص
|--------------------------------------------------------------------------
|
| مسیرهای ثابت (`/ask/new`) پیش از `{uuid}` می‌آیند.
|
*/

Route::prefix('ask')->name('expert.')->group(function (): void {
    Route::get('/', [QuestionController::class, 'index'])->name('index');

    Route::middleware('auth')->group(function (): void {
        Route::get('/new', [QuestionController::class, 'create'])->name('create');
        Route::post('/', [QuestionController::class, 'store'])
            ->middleware('throttle:'.(int) config('expert.ask_per_hour', 5).',60')
            ->name('store');

        Route::post('/{uuid}/answers', [AnswerController::class, 'store'])
            ->whereUuid('uuid')
            ->middleware('can:expert.answer')
            ->name('answers.store');

        Route::post('/{uuid}/accept/{answer}', [QuestionController::class, 'accept'])
            ->whereUuid(['uuid', 'answer'])
            ->name('accept');
    });

    Route::get('/{uuid}', [QuestionController::class, 'show'])->whereUuid('uuid')->name('show');
});

Route::middleware('auth')->prefix('workspace')->name('expert.')->group(function (): void {
    Route::get('/questions', [ExpertWorkspaceController::class, 'mine'])->name('mine');
    Route::get('/expert', [ExpertWorkspaceController::class, 'queue'])
        ->middleware('can:expert.answer')
        ->name('queue');
});
