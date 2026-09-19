<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Enums;

enum ProfileStatus: string
{
    /** درخواست ثبت شده و منتظر تأیید مدیر است. */
    case Pending = 'pending';

    /** تأیید شده و دسترسی‌هایش به حساب اضافه شده است. */
    case Active = 'active';

    /** مدیر موقتاً معلق کرده است. */
    case Suspended = 'suspended';

    /** کاربر خودش غیرفعال کرده است. داده‌ها دست‌نخورده می‌مانند. */
    case Disabled = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار تأیید مدیر',
            self::Active => 'فعال',
            self::Suspended => 'معلق‌شده',
            self::Disabled => 'غیرفعال',
        };
    }

    public function badgeTone(): string
    {
        return match ($this) {
            self::Active => 'primary',
            self::Pending => 'caution',
            self::Suspended => 'danger',
            self::Disabled => 'neutral',
        };
    }

    /** فقط پروفایل فعال، دسترسی به حساب اضافه می‌کند. */
    public function grantsAccess(): bool
    {
        return $this === self::Active;
    }
}
