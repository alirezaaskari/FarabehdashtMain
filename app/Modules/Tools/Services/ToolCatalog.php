<?php

declare(strict_types=1);

namespace App\Modules\Tools\Services;

use App\Modules\Tools\Domain\Enums\ToolAvailability;
use App\Modules\Tools\Domain\Enums\ToolCategory;
use App\Modules\Tools\Domain\ResolvedTool;
use App\Modules\Tools\Domain\Tool;
use App\Modules\Tools\Domain\ToolDefinition;
use Farabehdasht\CalcEngine\FormulaRegistry;

/**
 * تنها جایی که تعریف ابزار (کد)، وضعیتش (جدول) و رابطه‌اش (موتور محاسبات) به
 * هم می‌رسند.
 *
 * هیچ Controller و هیچ قالبی مستقیم از جدول `tools` یا از رجیستری فرمول
 * نمی‌پرسد؛ همه از اینجا می‌پرسند.
 */
final class ToolCatalog
{
    /** @var array<string, ToolDefinition>|null */
    private ?array $definitions = null;

    /** @var array<string, Tool>|null */
    private ?array $state = null;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly FormulaRegistry $registry,
        private readonly array $config,
    ) {}

    /**
     * @return array<string, ToolDefinition>
     */
    public function definitions(): array
    {
        if ($this->definitions !== null) {
            return $this->definitions;
        }

        /** @var array<string, array<string, mixed>> $catalog */
        $catalog = $this->config['catalog'] ?? [];

        $definitions = [];

        /** @var array<string, array<string, mixed>> $guides */
        $guides = $this->config['guides'] ?? [];

        foreach ($catalog as $slug => $entry) {
            $definitions[$slug] = ToolDefinition::fromConfig($slug, [...$entry, ...($guides[$slug] ?? [])]);
        }

        return $this->definitions = $definitions;
    }

    public function has(string $slug): bool
    {
        return isset($this->definitions()[$slug]);
    }

    /**
     * @throws ToolNotFound
     */
    public function resolve(string $slug): ResolvedTool
    {
        $definition = $this->definitions()[$slug] ?? throw ToolNotFound::slug($slug);

        $state = $this->state()[$slug] ?? null;
        $pinned = $state?->pinned_version;

        // نسخه سنجاق‌شده‌ای که دیگر در موتور نیست نباید صفحه را بترکاند؛
        // آخرین نسخه اجرا می‌شود و سنجاق نادیده گرفته می‌شود.
        $pinnedExists = $pinned !== null && $this->registry->has($definition->formulaId, $pinned);

        return new ResolvedTool(
            definition: $definition,
            formula: $this->registry->get($definition->formulaId, $pinnedExists ? $pinned : null),
            availability: $this->availabilityOf($state),
            versionPinned: $pinnedExists,
            reviewedAt: $state?->reviewed_at,
        );
    }

    /**
     * @return list<ResolvedTool>
     */
    public function all(): array
    {
        return array_map($this->resolve(...), array_keys($this->definitions()));
    }

    /**
     * فقط ابزارهایی که کاربر می‌تواند باز کند.
     *
     * @return list<ResolvedTool>
     */
    public function usable(): array
    {
        return array_values(array_filter($this->all(), static fn (ResolvedTool $t): bool => $t->usable()));
    }

    /**
     * ابزارهای قابل استفاده، گروه‌بندی‌شده — ترتیب گروه‌ها ترتیب enum است.
     *
     * @return array<string, array{category: ToolCategory, tools: list<ResolvedTool>}>
     */
    public function grouped(): array
    {
        $groups = [];

        foreach (ToolCategory::cases() as $category) {
            $tools = array_values(array_filter(
                $this->usable(),
                static fn (ResolvedTool $t): bool => $t->definition->category === $category,
            ));

            if ($tools !== []) {
                $groups[$category->value] = ['category' => $category, 'tools' => $tools];
            }
        }

        return $groups;
    }

    /**
     * نسخه‌های موجود یک ابزار در موتور — برای انتخاب در پنل مدیریت.
     *
     * @return list<string>
     */
    public function availableVersions(string $slug): array
    {
        $definition = $this->definitions()[$slug] ?? throw ToolNotFound::slug($slug);

        return $this->registry->versionsOf($definition->formulaId);
    }

    /**
     * حافظه وضعیت را دور می‌ریزد؛ پس از هر تغییر در جدول لازم است.
     */
    public function forget(): void
    {
        $this->state = null;
    }

    /**
     * @return array<string, Tool>
     */
    private function state(): array
    {
        if ($this->state !== null) {
            return $this->state;
        }

        /** @var array<string, Tool> $rows */
        $rows = Tool::query()->get()->keyBy('slug')->all();

        return $this->state = $rows;
    }

    private function availabilityOf(?Tool $state): ToolAvailability
    {
        // نبودِ ردیف یعنی «هنوز همگام نشده»، نه «غیرفعال»: ابزار تازه باید
        // بلافاصله کار کند، نه اینکه تا اجرای یک دستور نامرئی بماند.
        if ($state === null) {
            return ToolAvailability::NotReviewed;
        }

        if (! $state->is_enabled) {
            return ToolAvailability::Disabled;
        }

        if ($state->reviewed_at === null) {
            return ToolAvailability::NotReviewed;
        }

        $interval = (int) ($this->config['review_interval_days'] ?? 365);

        return $state->reviewed_at->addDays($interval)->isPast()
            ? ToolAvailability::ReviewOverdue
            : ToolAvailability::Available;
    }
}
