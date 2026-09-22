<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Domain\Import;

/**
 * چیزی که ورود CSV با یک ردیف انجام می‌دهد.
 *
 * چهار حالت، نه سه: «بدون تغییر» از «به‌روزرسانی» جداست چون مدیر باید بتواند
 * فایلی را که چیزی در آن عوض نشده، با خیال راحت رد کند — بدون خواندن هر ردیف.
 */
enum ImportAction: string
{
    case Create = 'create';
    case Update = 'update';
    case Unchanged = 'unchanged';
    case Invalid = 'invalid';

    public function label(): string
    {
        return match ($this) {
            self::Create => 'ماده تازه',
            self::Update => 'به‌روزرسانی',
            self::Unchanged => 'بدون تغییر',
            self::Invalid => 'نامعتبر',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Create => 'primary',
            self::Update => 'caution',
            self::Unchanged => 'neutral',
            self::Invalid => 'danger',
        };
    }
}
