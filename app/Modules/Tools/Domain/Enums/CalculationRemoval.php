<?php

declare(strict_types=1);

namespace App\Modules\Tools\Domain\Enums;

/** سرانجام درخواست حذف یک محاسبه ذخیره‌شده. */
enum CalculationRemoval: string
{
    /** جایی ارجاع نداشت و ردیفش پاک شد. */
    case Deleted = 'deleted';

    /** قرائت پروژه یا گزارشی به آن ارجاع دارد؛ فقط از فهرست بیرون رفت. */
    case Archived = 'archived';

    public function message(): string
    {
        return match ($this) {
            self::Deleted => 'محاسبه حذف شد.',
            self::Archived => 'محاسبه از فهرست شما حذف شد. چون در پروژه یا گزارشی به کار رفته، '
                .'داده‌اش برای همان‌جا نگه داشته می‌شود.',
        };
    }
}
