<?php

declare(strict_types=1);

namespace App\Modules\Tools\Domain;

use App\Modules\Tools\Domain\Enums\ToolCategory;

/**
 * تعریف ثابت یک ابزار — آنچه در کد نوشته می‌شود و مدیر عوضش نمی‌کند.
 *
 * ابزار با فرمول یکی نیست: `stel-ppm` و `twa-ppm` هر دو روی رابطه
 * `twa-ppm` سوارند و فقط پنجره زمانی و متن راهنمایشان فرق می‌کند.
 */
final readonly class ToolDefinition
{
    /**
     * @param  array<string, string>  $hints  راهنمای فارسی هر ورودی
     * @param  array<string, float>  $defaults  مقدار پیش‌فرض فرم
     * @param  int  $rows  تعداد ردیف اولیه برای ورودی‌های فهرستی
     * @param  float|null  $window  مجموع مدت مورد انتظار به دقیقه؛ فقط هشدار نمایشی
     * @param  string|null  $alternative  ابزار هم‌خانواده با رابطه دیگر (WBGT داخلی و بیرونی)
     * @param  string|null  $variant  برچسب کوتاه این ابزار در سوییچ میان دو هم‌خانواده
     */
    public function __construct(
        public string $slug,
        public string $formulaId,
        public ToolCategory $category,
        public string $title,
        public string $summary,
        public array $hints = [],
        public array $defaults = [],
        public int $rows = 1,
        public ?float $window = null,
        public ?string $alternative = null,
        public ?string $variant = null,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(string $slug, array $config): self
    {
        /** @var array<string, string> $hints */
        $hints = $config['hints'] ?? [];

        /** @var array<string, float> $defaults */
        $defaults = $config['defaults'] ?? [];

        return new self(
            slug: $slug,
            formulaId: (string) $config['formula'],
            category: ToolCategory::from((string) $config['category']),
            title: (string) $config['title'],
            summary: (string) $config['summary'],
            hints: $hints,
            defaults: $defaults,
            rows: (int) ($config['rows'] ?? 1),
            window: isset($config['window']) ? (float) $config['window'] : null,
            alternative: isset($config['alternative']) ? (string) $config['alternative'] : null,
            variant: isset($config['variant']) ? (string) $config['variant'] : null,
        );
    }

    public function hintFor(string $inputKey): ?string
    {
        return $this->hints[$inputKey] ?? null;
    }

    public function defaultFor(string $inputKey): ?float
    {
        return $this->defaults[$inputKey] ?? null;
    }
}
