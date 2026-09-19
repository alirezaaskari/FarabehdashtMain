<?php

declare(strict_types=1);

use App\Modules\Health\Http\Controllers\HealthCheckController;
use Illuminate\Support\Facades\Route;

Route::get('/_health', HealthCheckController::class)->name('health.check');
