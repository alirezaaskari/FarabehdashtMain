<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Middleware;

use App\Models\User;
use App\Modules\Admin\Services\AdminAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * یک صفحه پنل که توانایی مشخصی می‌خواهد.
 *
 * این‌جا ۴۰۳ درست است، نه ۴۰۴: کاربر مدیر است و وجود صفحه برایش راز نیست؛
 * فقط این بخش مال او نیست.
 */
final readonly class RequireAdminAbility
{
    public function __construct(private AdminAccess $access) {}

    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User && $this->access->allows($user, $ability), 403);

        return $next($request);
    }
}
