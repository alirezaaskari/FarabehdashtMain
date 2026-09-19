<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'فعال',
            self::Suspended => 'معلق‌شده',
        };
    }

    public function canSignIn(): bool
    {
        return $this === self::Active;
    }
}
