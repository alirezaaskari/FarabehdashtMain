<?php

declare(strict_types=1);

namespace App\Modules\Expert\Domain\Enums;

/**
 * چه کسی پرسش را می‌بیند (DEC-41).
 *
 * خصوصی یعنی فقط پرسش‌کننده، مشاوران تأییدشده و مدیر؛ نه در نقشه سایت،
 * نه در جست‌وجو، نه در فهرست عمومی.
 */
enum QuestionVisibility: string
{
    case Public = 'public';
    case Private = 'private';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'عمومی',
            self::Private => 'فقط برای من و پاسخ‌دهنده',
        };
    }
}
