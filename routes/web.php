<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
| صفحه مرجع سیستم طراحی.
| فقط بیرون از production در دسترس است — یک ابزار داخلی تیم، نه صفحه محصول.
*/
if (! app()->isProduction()) {
    Route::view('/design-system', 'design-system')->name('design-system');
}
