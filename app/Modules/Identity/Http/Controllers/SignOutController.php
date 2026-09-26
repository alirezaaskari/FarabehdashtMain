<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class SignOutController
{
    public function __construct(private StatefulGuard $guard) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $this->guard->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // صفحه‌های ابزاری که سرویس‌ورکر برای کار آفلاین نگه داشته، نام کاربر را
        // در سربرگ دارند؛ روی گوشی مشترک نباید پس از خروج بمانند.
        return to_route('home')->header('Clear-Site-Data', '"cache"');
    }
}
