<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers;

use App\Modules\Core\Services\HomePage;
use Illuminate\Contracts\View\View;

/**
 * صفحه اصلی.
 *
 * هیچ منطقی ندارد جز صدازدن سرویس (قاعده ۴): چه چیزی نشان داده شود را
 * ماژول‌ها تعیین می‌کنند، نه این کنترلر.
 */
final readonly class HomeController
{
    public function __invoke(HomePage $page): View
    {
        return view('core::home', ['sections' => $page->sections()]);
    }
}
