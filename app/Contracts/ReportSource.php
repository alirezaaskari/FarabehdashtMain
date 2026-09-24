<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Reporting\ReportData;
use App\Support\Reporting\ReportSourceOption;

/**
 * ماژولی که داده‌اش می‌تواند منبع یک گزارش باشد.
 *
 * گزارش‌ساز نمی‌داند پروژه یا محاسبه چیست؛ هر ماژول صاحب داده این قرارداد را
 * پیاده و با برچسب {@see self::TAG} ثبت می‌کند — همان الگوی
 * `WorkspaceWidgetSource`. خاموش‌شدن یک ماژول فقط گزینه آن را از مرحله اول
 * گزارش‌ساز برمی‌دارد.
 *
 * مالکیت بخشی از هر پرسش است: هیچ منبعی داده کاربر دیگری را برنمی‌گرداند،
 * حتی اگر شناسه درست حدس زده شود.
 */
interface ReportSource
{
    public const TAG = 'reports.sources';

    /** کلید پایدار منبع، همان که در ردیف گزارش ذخیره می‌شود. */
    public function key(): string;

    public function label(): string;

    /** آیا کاربر می‌تواند چند گزینه را با هم در یک گزارش بیاورد. */
    public function multiple(): bool;

    /** @return list<ReportSourceOption> */
    public function options(int $userId): array;

    /**
     * داده تازه منبع، یا null اگر هیچ‌کدام از مراجع مال این کاربر نباشد.
     *
     * @param  list<string>  $references
     */
    public function load(int $userId, array $references): ?ReportData;
}
