<?php

declare(strict_types=1);

use App\Modules\Core\Http\Controllers\HomeController;
use App\Modules\Core\Http\Controllers\RobotsController;
use App\Modules\Core\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/sitemap.xml', SitemapController::class)->name('core.sitemap');
Route::get('/robots.txt', RobotsController::class)->name('core.robots');
