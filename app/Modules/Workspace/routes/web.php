<?php

declare(strict_types=1);

use App\Modules\Workspace\Http\Controllers\DashboardController;
use App\Modules\Workspace\Http\Controllers\LegalController;
use App\Modules\Workspace\Http\Controllers\NotificationController;
use App\Modules\Workspace\Http\Controllers\SearchController;
use App\Modules\Workspace\Http\Controllers\SearchSuggestController;
use App\Modules\Workspace\Http\Controllers\StatusController;
use App\Modules\Workspace\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::name('workspace.')->group(function (): void {

    Route::middleware('auth')->prefix('workspace')->group(function (): void {
        Route::get('/', [DashboardController::class, 'show'])->name('dashboard');
        Route::post('/view', [DashboardController::class, 'switch'])->name('view');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::get('/notifications/{uuid}', [NotificationController::class, 'open'])
            ->whereUuid('uuid')
            ->name('notifications.open');

        Route::get('/wallet', WalletController::class)->name('wallet');
    });

    Route::get('/status', [StatusController::class, 'show'])->name('status');
    Route::post('/status/{incident:uuid}/subscribe', [StatusController::class, 'subscribe'])
        ->middleware('auth')
        ->name('status.subscribe');

    Route::middleware('auth')->group(function (): void {
        Route::get('/legal/accept', [LegalController::class, 'accept'])->name('legal.accept');
        Route::post('/legal/accept', [LegalController::class, 'store'])->name('legal.accept.store');
    });

    Route::get('/legal/{document}', [LegalController::class, 'show'])->name('legal.show');
    Route::get('/legal/{document}/v{version}', [LegalController::class, 'version'])
        ->whereNumber('version')
        ->name('legal.version');

    Route::get('/search', SearchController::class)->name('search');
    Route::get('/search/suggest', SearchSuggestController::class)
        ->middleware('throttle:60,1')
        ->name('search.suggest');
});
