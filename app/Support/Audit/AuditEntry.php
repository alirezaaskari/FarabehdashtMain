<?php

declare(strict_types=1);

namespace App\Support\Audit;

/**
 * یک ردیف دفتر رویداد، پیش از نوشته‌شدن.
 *
 * قاعده ۷ پروژه: هر تغییر داده حساس یک Event منتشر می‌کند تا Audit Log مستقل
 * بماند. این DTO زبان مشترک آن رویدادهاست و در app/Support است تا هیچ ماژولی
 * برای ثبت رویداد به ماژول Core وابسته نشود.
 *
 * `before` و `after` فقط فیلدهای تغییرکرده را نگه می‌دارند، نه کل رکورد؛ دفتر
 * رویداد جای نسخه‌برداری از داده نیست (آن کار ContentRevision است).
 */
final readonly class AuditEntry
{
    /**
     * @param  string  $action  شناسه کار، مثل `profile.approved` — همیشه با نقطه و به انگلیسی
     * @param  string|null  $subjectType  کلاس موجودی که تغییر کرد
     * @param  int|string|null  $subjectId  شناسه همان موجودی
     * @param  int|null  $actorId  کاربری که کار را انجام داد؛ null یعنی سیستم
     * @param  array<string, mixed>  $before  مقدار پیشین فیلدهای تغییرکرده
     * @param  array<string, mixed>  $after  مقدار تازه همان فیلدها
     * @param  array<string, mixed>  $context  هر چیز دیگری که بعداً برای فهمیدن ماجرا لازم است
     */
    public function __construct(
        public string $action,
        public ?string $subjectType = null,
        public int|string|null $subjectId = null,
        public ?int $actorId = null,
        public array $before = [],
        public array $after = [],
        public array $context = [],
    ) {}
}
