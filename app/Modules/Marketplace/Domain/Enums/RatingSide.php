<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Enums;

/** امتیازدهنده کدام طرف قرارداد است. */
enum RatingSide: string
{
    case Client = 'client';
    case Provider = 'provider';

    /** نام طرفی که امتیاز را گرفته. */
    public function rateeLabel(): string
    {
        return match ($this) {
            self::Client => 'مجری',
            self::Provider => 'کارفرما',
        };
    }
}
