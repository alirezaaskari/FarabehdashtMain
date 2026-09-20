<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Domain\Enums;

/**
 * نوع یک نکته درباره ماده.
 *
 * سه فهرست صفحه ماده — مسیرهای مواجهه، علائم و اثرات، حفاظت فردی — یک جدول
 * با یک ستون نوع‌اند و نه سه جدول. ساختارشان یکی است (فهرست جمله کوتاه با
 * ترتیب) و سه جدول یعنی سه بار همان کد.
 */
enum FactKind: string
{
    case Route = 'route';
    case Symptom = 'symptom';
    case Protection = 'protection';

    public function label(): string
    {
        return match ($this) {
            self::Route => 'مسیرهای مواجهه',
            self::Symptom => 'علائم و اثرات',
            self::Protection => 'حفاظت فردی',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Route => 'wind',
            self::Symptom => 'pulse',
            self::Protection => 'shield',
        };
    }
}
