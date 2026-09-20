<?php

declare(strict_types=1);

namespace App\Modules\Tools\Filament\Pages;

use App\Modules\Tools\Actions\UpdateToolSettings;
use App\Modules\Tools\Services\ToolCatalog;
use App\Support\JalaliDate;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

/**
 * مدیریت ابزارها و نسخه فرمولشان.
 *
 * سه کار و نه بیشتر: روشن/خاموش کردن ابزار، انتخاب نسخه فرمول، ثبت بازبینی
 * علمی. **ساختن** نسخه تازه این‌جا ممکن نیست و نباید باشد — نسخه تازه یعنی
 * کلاس تازه و استقرار تازه (ADR-0005). پنلی که بتواند رابطه را عوض کند،
 * نسخه‌گذاری را از مسیر بازبینی کد بیرون می‌برد.
 *
 * پیش از عوض‌کردن نسخه، تعداد محاسبه‌های متأثر نمایش داده می‌شود. خودِ آن
 * محاسبه‌ها عوض نمی‌شوند و با نسخه خودشان بازتولید می‌شوند؛ ولی مدیر باید
 * بداند تصمیمش چند گزارش را از نسخه روز جدا می‌کند.
 */
final class ToolsPage extends Page
{
    public const ABILITY = 'admin.tools.manage';

    protected static ?string $slug = 'tools';

    protected static ?int $navigationSort = 40;

    protected string $view = 'tools::filament.pages.tools';

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    public static function getNavigationLabel(): string
    {
        return 'ابزارها';
    }

    public function getTitle(): string
    {
        return 'ابزارها و نسخه فرمول';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    public function mount(ToolCatalog $catalog, UpdateToolSettings $settings): void
    {
        $this->load($catalog, $settings);
    }

    public function setEnabled(string $slug, bool $enabled, ToolCatalog $catalog, UpdateToolSettings $settings): void
    {
        $settings->setAvailability($slug, $enabled, $this->actorId());

        $this->load($catalog, $settings);
    }

    /**
     * رشته خالی یعنی «همیشه آخرین نسخه» — برداشتن سنجاق، نه سنجاق‌کردن هیچ.
     */
    public function pin(string $slug, string $version, ToolCatalog $catalog, UpdateToolSettings $settings): void
    {
        $settings->pinVersion($slug, $version === '' ? null : $version, $this->actorId());

        $this->load($catalog, $settings);
    }

    public function markReviewed(string $slug, ToolCatalog $catalog, UpdateToolSettings $settings): void
    {
        $settings->markReviewed($slug, $this->actorId());

        $this->load($catalog, $settings);
    }

    private function load(ToolCatalog $catalog, UpdateToolSettings $settings): void
    {
        $catalog->forget();

        $this->rows = array_map(
            static function (string $slug) use ($catalog, $settings): array {
                $tool = $catalog->resolve($slug);

                return [
                    'slug' => $slug,
                    'title' => $tool->definition->title,
                    'category' => $tool->definition->category->label(),
                    'formula' => $tool->formula->id(),
                    'version' => $tool->version(),
                    'pinned' => $tool->versionPinned ? $tool->version() : '',
                    'versions' => $catalog->availableVersions($slug),
                    'enabled' => $tool->usable(),
                    'availability' => $tool->availability->label(),
                    'reviewed' => $tool->reviewedAt === null ? null : JalaliDate::short($tool->reviewedAt),
                    'affected' => $settings->affectedCalculations($slug),
                ];
            },
            array_keys($catalog->definitions()),
        );
    }

    private function actorId(): ?int
    {
        $id = Auth::id();

        return is_int($id) ? $id : null;
    }
}
