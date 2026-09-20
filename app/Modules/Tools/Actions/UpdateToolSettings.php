<?php

declare(strict_types=1);

namespace App\Modules\Tools\Actions;

use App\Modules\Tools\Domain\SavedCalculation;
use App\Modules\Tools\Domain\Tool;
use App\Modules\Tools\Events\FormulaVersionPinned;
use App\Modules\Tools\Events\ToolAvailabilityChanged;
use App\Modules\Tools\Events\ToolReviewed;
use App\Modules\Tools\Services\ToolCatalog;
use App\Modules\Tools\Services\ToolNotFound;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;

/**
 * تغییرهایی که مدیر روی یک ابزار انجام می‌دهد.
 *
 * هر سه کار رویداد منتشر می‌کنند (قاعده ۷): خاموش‌کردن ابزار، عوض‌کردن نسخه
 * فرمول و ثبت بازبینی علمی، همه ادعاهایی‌اند که باید رد داشته باشند.
 *
 * نسخه فرمول این‌جا **ساخته نمی‌شود**، فقط انتخاب می‌شود. ساختن نسخه تازه کار
 * کد و استقرار است، نه کار پنل — این تصمیم بخش ۶ است و پنل دورش نمی‌زند.
 */
final readonly class UpdateToolSettings
{
    public function __construct(
        private ToolCatalog $catalog,
        private Dispatcher $events,
    ) {}

    public function setAvailability(string $slug, bool $enabled, ?int $actorId): Tool
    {
        $tool = $this->row($slug);
        $was = $tool->exists ? $tool->is_enabled : true;

        $tool->is_enabled = $enabled;
        $tool->save();

        $this->catalog->forget();

        if ($was !== $enabled) {
            $this->events->dispatch(new ToolAvailabilityChanged($slug, $was, $enabled, $actorId));
        }

        return $tool;
    }

    /**
     * @param  string|null  $version  null یعنی «همیشه آخرین نسخه»
     *
     * @throws ToolNotFound
     * @throws InvalidArgumentException اگر نسخه خواسته‌شده در موتور نباشد
     */
    public function pinVersion(string $slug, ?string $version, ?int $actorId): Tool
    {
        $available = $this->catalog->availableVersions($slug);

        if ($version !== null && ! in_array($version, $available, strict: true)) {
            throw new InvalidArgumentException(sprintf(
                'نسخه «%s» برای ابزار «%s» در موتور محاسبات وجود ندارد.',
                $version,
                $slug,
            ));
        }

        $tool = $this->row($slug);
        $previous = $tool->pinned_version;

        $tool->pinned_version = $version;
        $tool->save();

        $this->catalog->forget();

        if ($previous !== $version) {
            $this->events->dispatch(new FormulaVersionPinned(
                $slug,
                $previous,
                $version,
                $this->affectedCalculations($slug),
                $actorId,
            ));
        }

        return $tool;
    }

    public function markReviewed(string $slug, ?int $actorId): Tool
    {
        $tool = $this->row($slug);
        $tool->reviewed_at = now();
        $tool->save();

        $this->catalog->forget();

        $this->events->dispatch(new ToolReviewed($slug, $tool->reviewed_at->toIso8601String(), $actorId));

        return $tool;
    }

    /**
     * چند محاسبه ذخیره‌شده با نسخه فعلی این ابزار ثبت شده‌اند.
     *
     * پنل این عدد را پیش از عوض‌کردن نسخه نشان می‌دهد. خودِ آن محاسبه‌ها عوض
     * نمی‌شوند — با نسخه خودشان بازتولید می‌شوند — ولی مدیر باید بداند تصمیمش
     * چند گزارش را از نسخه روز جدا می‌کند.
     */
    public function affectedCalculations(string $slug): int
    {
        $tool = $this->catalog->resolve($slug);

        return SavedCalculation::query()
            ->usingFormulaVersion($tool->formula->id(), $tool->version())
            ->count();
    }

    /**
     * @throws ToolNotFound
     */
    private function row(string $slug): Tool
    {
        if (! $this->catalog->has($slug)) {
            throw ToolNotFound::slug($slug);
        }

        return Tool::query()->firstOrNew(['slug' => $slug], ['is_enabled' => true]);
    }
}
