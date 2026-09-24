<?php

declare(strict_types=1);

namespace App\Modules\Reports\Actions;

use App\Models\User;
use App\Modules\Reports\Domain\Enums\ReportStatus;
use App\Modules\Reports\Domain\Report;
use App\Modules\Reports\Services\ReportSources;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * مرحله اول گزارش‌ساز: ساخت پیش‌نویس از یک منبع.
 *
 * ساختن پیش‌نویس سقف اشتراک ندارد (DEC-29) — کاربر رایگان هم همه مراحل را
 * می‌رود و پیش‌نمایش را می‌بیند؛ فقط صدور Pro می‌خواهد. ارزش پیش از دیوار.
 */
final readonly class StartReport
{
    public function __construct(private ReportSources $sources) {}

    /** @param  list<string>  $references */
    public function handle(User $user, string $sourceKey, array $references): Report
    {
        $source = $this->sources->find($sourceKey)
            ?? throw new InvalidArgumentException('این منبع گزارش در دسترس نیست.');

        $references = array_values(array_unique(array_filter($references, static fn (string $r): bool => $r !== '')));

        if (! $source->multiple()) {
            $references = array_slice($references, 0, 1);
        }

        if ($references === []) {
            throw new InvalidArgumentException('دست‌کم یک مورد را برای گزارش انتخاب کنید.');
        }

        $data = $source->load((int) $user->getKey(), $references)
            ?? throw new InvalidArgumentException('مورد انتخاب‌شده پیدا نشد.');

        if ($data->measurements === []) {
            throw new InvalidArgumentException('مورد انتخاب‌شده هنوز هیچ نتیجه‌ای ندارد که گزارش شود.');
        }

        return Report::query()->create([
            'uuid' => (string) Str::uuid7(),
            'user_id' => $user->getKey(),
            'status' => ReportStatus::Draft,
            'source_key' => $source->key(),
            'source_references' => $references,
            'title' => $data->suggestedTitle ?? $data->sourceTitle,
            'client_name' => $data->suggestedClient,
            'author_name' => $user->name,
        ]);
    }
}
