<?php

declare(strict_types=1);

namespace App\Support\Reporting;

/**
 * یک گزینه قابل انتخاب در مرحله اول گزارش‌ساز: یک پروژه، یا یک محاسبه.
 */
final readonly class ReportSourceOption
{
    public function __construct(
        public string $reference,
        public string $title,
        public ?string $meta = null,
    ) {}
}
