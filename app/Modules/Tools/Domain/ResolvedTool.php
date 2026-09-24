<?php

declare(strict_types=1);

namespace App\Modules\Tools\Domain;

use App\Modules\Tools\Domain\Enums\ToolAvailability;
use App\Support\PersianDigits;
use Farabehdasht\CalcEngine\Formula;
use Illuminate\Support\Carbon;

/**
 * ابزار، آماده نمایش: تعریف ثابتش، رابطه‌ای که واقعاً اجرا می‌شود، و وضعیتش.
 *
 * «رابطه‌ای که واقعاً اجرا می‌شود» مهم است: اگر مدیر نسخه‌ای را سنجاق کرده
 * باشد، همان اجرا می‌شود، وگرنه آخرین نسخه.
 */
final readonly class ResolvedTool
{
    public function __construct(
        public ToolDefinition $definition,
        public Formula $formula,
        public ToolAvailability $availability,
        public bool $versionPinned,
        public ?Carbon $reviewedAt = null,
    ) {}

    public function slug(): string
    {
        return $this->definition->slug;
    }

    public function version(): string
    {
        return $this->formula->version();
    }

    /**
     * نسخه برای متن فارسی: «۱٫۰»، یا «۱٫۲٫۳» وقتی وصله دارد.
     *
     * شناسه کامل لاتین (`version()`) برای ردیابی و چاپ می‌ماند.
     */
    public function displayVersion(): string
    {
        $parts = explode('.', $this->version());

        if (count($parts) === 3 && $parts[2] === '0') {
            array_pop($parts);
        }

        return PersianDigits::from(implode('٫', $parts));
    }

    public function usable(): bool
    {
        return $this->availability->usable();
    }
}
