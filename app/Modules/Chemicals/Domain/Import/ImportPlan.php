<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Domain\Import;

/**
 * گزارش تغییرات یک فایل CSV — معیار پذیرش این بخش.
 *
 * «ورود CSV قبل از اجرا گزارش تغییرات می‌دهد»: این شیء همان گزارش است.
 * `apply()` روی همین شیء صدا زده می‌شود، نه روی فایل خام — یعنی آنچه مدیر
 * تأیید می‌کند دقیقاً همان چیزی است که نوشته می‌شود.
 */
final readonly class ImportPlan
{
    /** @param  list<ImportRowResult>  $rows */
    public function __construct(public array $rows) {}

    /** @return list<ImportRowResult> */
    public function of(ImportAction $action): array
    {
        return array_values(array_filter($this->rows, static fn (ImportRowResult $r): bool => $r->action === $action));
    }

    public function count(ImportAction $action): int
    {
        return count($this->of($action));
    }

    public function hasErrors(): bool
    {
        return $this->count(ImportAction::Invalid) > 0;
    }

    /** آیا چیزی برای نوشتن هست — فایلی که همه ردیف‌هایش بدون تغییرند، اجرا نمی‌شود. */
    public function hasWritableChanges(): bool
    {
        return $this->count(ImportAction::Create) > 0 || $this->count(ImportAction::Update) > 0;
    }
}
