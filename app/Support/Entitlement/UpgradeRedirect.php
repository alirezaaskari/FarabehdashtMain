<?php

declare(strict_types=1);

namespace App\Support\Entitlement;

use Illuminate\Http\RedirectResponse;

/**
 * پاسخ HTTP به یک رد لایه دسترسی: بردن کاربر به گذرگاه تبدیل.
 *
 * چسب HTTP است، نه منطق تجاری — تصمیم را همان اکشن گرفته. این‌جاست تا هر
 * ماژولی که سقف دارد همین یک رفتار را داشته باشد و کاربر در دو صفحه دو
 * تجربه متفاوت نبیند.
 *
 * وقتی ماژول درآمدزایی خاموش است مسیر گذرگاه وجود ندارد؛ آن‌وقت کاربر به
 * صفحه قبل برمی‌گردد و پیام را می‌بیند، نه اینکه به ۴۰۴ بخورد.
 */
final readonly class UpgradeRedirect
{
    public static function from(EntitlementDenied $denied): RedirectResponse
    {
        $decision = $denied->decision;

        if ($decision->upgradeUrl === null) {
            return back()->with('entitlement', $decision->message);
        }

        return redirect()->to($decision->upgradeUrl);
    }
}
