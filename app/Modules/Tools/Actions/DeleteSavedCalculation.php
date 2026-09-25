<?php

declare(strict_types=1);

namespace App\Modules\Tools\Actions;

use App\Contracts\CalculationReferences;
use App\Modules\Tools\Domain\Enums\CalculationRemoval;
use App\Modules\Tools\Domain\SavedCalculation;
use App\Modules\Tools\Events\CalculationRemoved;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * حذف محاسبه ذخیره‌شده به خواست کاربر (تصمیم Alireza: «حذف واقعی»).
 *
 * اگر هیچ ماژولی به آن ارجاع نداشته باشد، ردیف پاک می‌شود. اگر قرائت پروژه
 * یا گزارشی به آن تکیه دارد، فقط بایگانی می‌شود تا آن پروژه و گزارش
 * نشکنند؛ در هر دو حال از فهرست کاربر و سقف پلن رایگان بیرون می‌رود.
 */
final readonly class DeleteSavedCalculation
{
    /** @param  iterable<CalculationReferences>  $references */
    public function __construct(
        private iterable $references,
        private Dispatcher $events,
    ) {}

    public function handle(SavedCalculation $calculation): CalculationRemoval
    {
        $outcome = $this->isReferenced($calculation->uuid)
            ? CalculationRemoval::Archived
            : CalculationRemoval::Deleted;

        if ($outcome === CalculationRemoval::Deleted) {
            // مدل `delete()` را بسته است؛ این تنها راه پاک‌کردن است.
            SavedCalculation::query()->whereKey($calculation->getKey())->delete();
        } else {
            $calculation->forceFill(['archived_at' => now()])->save();
        }

        $this->events->dispatch(new CalculationRemoved($calculation->uuid, $calculation->user_id, $outcome));

        return $outcome;
    }

    private function isReferenced(string $uuid): bool
    {
        foreach ($this->references as $source) {
            if ($source->references($uuid)) {
                return true;
            }
        }

        return false;
    }
}
