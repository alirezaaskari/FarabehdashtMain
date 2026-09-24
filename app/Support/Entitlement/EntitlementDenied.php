<?php

declare(strict_types=1);

namespace App\Support\Entitlement;

use RuntimeException;

/**
 * کاری که لایه دسترسی اجازه‌اش را نداد.
 *
 * قاعده ۴ می‌گوید منطق تجاری در کنترلر نیست: اجرای سقف داخل اکشن است، و
 * کنترلر فقط این استثنا را می‌گیرد و کاربر را به گذرگاه تبدیل می‌فرستد. پس
 * هر مسیر تازه‌ای که فردا همان اکشن را صدا بزند، خودبه‌خود محافظت‌شده است.
 */
final class EntitlementDenied extends RuntimeException
{
    public function __construct(public readonly Decision $decision)
    {
        parent::__construct($decision->message);
    }
}
