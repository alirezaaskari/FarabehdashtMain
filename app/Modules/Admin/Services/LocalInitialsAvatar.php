<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * آواتار حروف اول، ساخته‌شده روی همین سرور.
 *
 * پیاده‌سازی پیش‌فرض Filament نام کاربر را داخل نشانی به `ui-avatars.com`
 * می‌فرستد. دو اشکال دارد: نام کاربر به سرویس بیرونی می‌رود (خلاف قاعده حریم
 * خصوصی پروژه)، و اگر آن سرویس در دسترس نباشد آواتارها خراب می‌شوند.
 *
 * این نسخه یک SVG را همان‌جا می‌سازد و به‌صورت data-uri برمی‌گرداند: بدون
 * درخواست شبکه، بدون فایل ذخیره‌شده، بدون وابستگی بیرونی.
 */
final readonly class LocalInitialsAvatar implements AvatarProvider
{
    public function get(Model|Authenticatable $record): string
    {
        $initials = $this->initials(Filament::getNameForDefaultAvatar($record));

        $svg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64">
                <rect width="64" height="64" rx="32" fill="#0f5f52"/>
                <text x="32" y="32" fill="#ffffff" font-family="system-ui, sans-serif"
                      font-size="26" font-weight="700" text-anchor="middle"
                      dominant-baseline="central">{$initials}</text>
            </svg>
            SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /** حداکثر دو حرف اول، از کلمه‌های نام. */
    private function initials(string $name): string
    {
        $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $letters = array_map(
            static fn (string $word): string => mb_substr(
                (string) preg_replace('/^[^\p{L}\p{N}]+/u', '', $word),
                0,
                1,
            ),
            array_slice($words, 0, 2),
        );

        $initials = implode('', array_filter($letters));

        return htmlspecialchars($initials === '' ? '؟' : $initials, ENT_XML1);
    }
}
