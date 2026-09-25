<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exceptions;

use App\Support\PersianDigits;
use RuntimeException;

/** ورود مدیر با ایمیل و رمز پذیرفته نشد؛ پیام برای نمایش به کاربر است. */
final class AdminSignInFailed extends RuntimeException
{
    public static function rejected(): self
    {
        return new self('ایمیل یا رمز عبور درست نیست.');
    }

    public static function lockedOut(int $seconds): self
    {
        $minutes = max(1, (int) ceil($seconds / 60));

        return new self(sprintf('تلاش‌های نادرست زیاد بود. %s دقیقه دیگر دوباره امتحان کنید.', PersianDigits::from($minutes)));
    }
}
