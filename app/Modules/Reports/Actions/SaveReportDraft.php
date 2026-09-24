<?php

declare(strict_types=1);

namespace App\Modules\Reports\Actions;

use App\Modules\Reports\Domain\Report;
use LogicException;

/**
 * ذخیره مرحله دوم و سوم. فقط پیش‌نویس ویرایش می‌شود؛ گزارش صادرشده تنها با
 * «اصلاح» — یعنی نسخه تازه — عوض می‌شود.
 */
final readonly class SaveReportDraft
{
    private const FIELDS = [
        'title', 'client_name', 'site', 'measured_on', 'author_name',
        'include_equipment', 'include_method', 'findings', 'recommendations',
    ];

    /** @param  array<string, mixed>  $attributes */
    public function handle(Report $report, array $attributes): Report
    {
        if (! $report->isDraft()) {
            throw new LogicException('گزارش صادرشده ویرایش نمی‌شود؛ برای اصلاح، نسخه تازه بسازید.');
        }

        $report->fill(array_intersect_key($attributes, array_flip(self::FIELDS)))->save();

        return $report;
    }
}
