<?php

declare(strict_types=1);

use App\Modules\Commerce\Http\Controllers\DownloadController;
use Illuminate\Support\Facades\Route;

Route::prefix('commerce')->name('commerce.')->group(function (): void {
    Route::get('/products/{product}/download', DownloadController::class)
        ->middleware(['auth', 'signed'])
        ->name('download');
});
