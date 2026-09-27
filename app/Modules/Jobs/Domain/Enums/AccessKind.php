<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Domain\Enums;

/** چه چیزی از کارجو به کارفرما نشان داده شد (DEC-68). */
enum AccessKind: string
{
    case Contact = 'contact';
    case Resume = 'resume';

    public function label(): string
    {
        return match ($this) {
            self::Contact => 'شماره و ایمیل',
            self::Resume => 'فایل رزومه',
        };
    }
}
