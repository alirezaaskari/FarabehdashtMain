<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Http\Middleware;

use App\Models\User;
use App\Modules\Workspace\Services\LegalLibrary;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * کاربر واردشده‌ای که نسخه اساسی تازه قوانین را نپذیرفته، پیش از هر صفحه
 * نیازمند ورود به صفحه پذیرش می‌رود (DEC-24).
 *
 * فقط مسیرهایی که میان‌افزار `auth` دارند: صفحات عمومی هرگز مسدود نمی‌شوند،
 * چون بستن دانشنامه پشت یک دکمه «می‌پذیرم» هیچ سودی برای کسی ندارد. خروج از
 * حساب و خود صفحه‌های حقوقی همیشه باز می‌مانند.
 *
 * در حالت «مشاهده به‌عنوان کاربر» کاری نمی‌کند: مدیر نباید به‌جای کاربر
 * بپذیرد و نباید پشت صفحه‌ای بماند که نمی‌تواند از آن رد شود.
 */
final readonly class RequireLegalAcceptance
{
    /** همان کلید نشستی که نوار «مشاهده به‌عنوان کاربر» در پوسته میزکار می‌خواند. */
    public const IMPERSONATION_SESSION_KEY = 'admin.impersonator_id';

    private const ALWAYS_OPEN = ['identity.signout', 'workspace.legal.*'];

    public function __construct(private LegalLibrary $library) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->applies($request)) {
            return $next($request);
        }

        /** @var User $user */
        $user = $request->user();

        if ($this->library->pendingFor((int) $user->getKey()) === []) {
            return $next($request);
        }

        if ($request->isMethod('GET')) {
            redirect()->setIntendedUrl($request->fullUrl());
        }

        return redirect()->route('workspace.legal.accept');
    }

    private function applies(Request $request): bool
    {
        $route = $request->route();

        if ($route === null || ! $request->user() instanceof User) {
            return false;
        }

        if ($request->hasSession() && $request->session()->has(self::IMPERSONATION_SESSION_KEY)) {
            return false;
        }

        if ($request->routeIs(...self::ALWAYS_OPEN)) {
            return false;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && ($middleware === 'auth' || str_starts_with($middleware, 'auth:'))) {
                return true;
            }
        }

        return false;
    }
}
