<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Domain\Import;

/**
 * تغییر یک ستون در یک ردیف به‌روزرسانی.
 *
 * مقدار قبلی و بعدی هر دو رشته‌اند، حتی برای جرم مولکولی، چون این DTO فقط
 * برای **نمایش** پیش‌نمایش است؛ تبدیل نوع کار خود اکشن نوشتن است.
 */
final readonly class FieldChange
{
    public function __construct(
        public string $field,
        public string $fieldLabel,
        public ?string $before,
        public ?string $after,
    ) {}
}
