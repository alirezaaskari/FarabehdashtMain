<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Middleware;

use App\Models\User;
use App\Modules\Admin\Services\AdminAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * فقط کسی که نقش مدیریتی دارد وارد پنل می‌شود.
 *
 * ۴۰۴ برمی‌گرداند، نه ۴۰۳: کاربر عادی که نشانی پنل را حدس زده، نباید تأیید
 * بگیرد که نشانی درست است.
 */
final readonly class EnsureAdminAccess
{
    public function __construct(private AdminAccess $access) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User && $this->access->isAdmin($user), 404);

        return $next($request);
    }
}
