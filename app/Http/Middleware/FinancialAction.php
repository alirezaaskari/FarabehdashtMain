<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\FinancialGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * میان‌افزار `financial` برای مسیرهایی که پرداخت شروع می‌کنند.
 *
 * پیش از کنترلر می‌ایستد، پس در حالت «مشاهده به‌عنوان کاربر» حتی سفارش
 * یا ثبت‌نام در انتظار پرداخت هم ساخته نمی‌شود.
 */
final readonly class FinancialAction
{
    public function __construct(private FinancialGuard $guard) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->guard->assertAllowed();

        return $next($request);
    }
}
