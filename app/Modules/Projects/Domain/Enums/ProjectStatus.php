<?php

declare(strict_types=1);

namespace App\Modules\Projects\Domain\Enums;

enum ProjectStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Completed = 'completed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'پیش‌نویس',
            self::Active => 'در جریان',
            self::Completed => 'تمام‌شده',
            self::Archived => 'بایگانی',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Active => 'primary',
            self::Completed => 'primary',
            self::Archived => 'neutral',
        };
    }

    /**
     * پروژه بایگانی‌شده فقط خوانده می‌شود.
     */
    public function editable(): bool
    {
        return $this !== self::Archived;
    }
}
