<?php

declare(strict_types=1);

namespace App\Modules\Reports\Services;

use App\Contracts\CalculationReferences;
use App\Modules\Reports\Domain\Report;

/**
 * گزارشی که محاسبه‌ای را منبع گرفته، آن محاسبه را نگه می‌دارد — پیش‌نویس هم،
 * چون هنوز از آن داده می‌خواند.
 *
 * منبع گزارش عمداً بررسی نمی‌شود: نام منبع مال ماژول ابزارهاست و UUID در
 * هر حال یکتاست. `source_references` آرایه JSON شناسه‌هاست. جست‌وجو با LIKE روی رشته
 * نقل‌قول‌دار است، نه تابع JSON، تا روی MariaDB و SQLite یکی کار کند؛ شناسه
 * UUID نویسه ویژه LIKE ندارد.
 */
final readonly class ReportCalculationReferences implements CalculationReferences
{
    public function references(string $calculationUuid): bool
    {
        return Report::query()
            ->where('source_references', 'like', '%"'.$calculationUuid.'"%')
            ->exists();
    }
}
