<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\SalesSwitch;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * مسیر فروشی که کلیدش خاموش است به صفحه اصلی می‌رود، نه به خطا.
 *
 * کاربرد: `->middleware('sales:file_sale')`. هیچ داده‌ای پنهان یا حذف
 * نمی‌شود؛ دانلود خریدهای قبلی و محیط یادگیری دوره‌های پرداخت‌شده این
 * میان‌افزار را ندارند و کار می‌کنند.
 */
final readonly class SalesOpen
{
    public function __construct(private SalesSwitch $sales) {}

    public function handle(Request $request, Closure $next, string $stream): Response
    {
        if ($this->sales->isOpen($stream)) {
            return $next($request);
        }

        return redirect()->to(Route::has('home') ? route('home') : '/')
            ->with('notice', 'این بخش فروش موقتاً متوقف است. خریدهای قبلی شما در میزکار در دسترس است.');
    }
}
