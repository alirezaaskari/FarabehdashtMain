<?php

declare(strict_types=1);

namespace App\Modules\Tools\Domain;

/**
 * چند ارزیابی پوسچر با یک روش، کنار هم: چند ایستگاه، یا یک ایستگاه پیش و پس از اصلاح.
 *
 * همه رشته‌اند و آماده نمایش (قاعده ۴). ستون «تغییر» فقط وقتی هست که دقیقاً
 * دو ارزیابی کنار هم باشند: اولی «پیش» است و دومی «پس».
 */
final readonly class AssessmentComparison
{
    /**
     * @param  list<array{uuid: string, title: string, date: string}>  $columns  به ترتیب ثبت، قدیمی‌تر اول
     * @param  list<array{label: string, values: list<string>, change: string|null, trend: string|null}>  $scores
     *                                                                                                             trend: better · worse · same
     * @param  list<array{label: string, values: list<string>}>  $differences  پاسخ‌هایی که میان ارزیابی‌ها فرق دارد
     */
    public function __construct(
        public string $method,
        public array $columns,
        public array $scores,
        public array $differences,
    ) {}

    public function isBeforeAfter(): bool
    {
        return count($this->columns) === 2;
    }
}
