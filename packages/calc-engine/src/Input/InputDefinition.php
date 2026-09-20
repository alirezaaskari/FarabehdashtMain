<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Input;

use Farabehdasht\CalcEngine\Unit;

/**
 * توصیف یک ورودی فرمول: نام، برچسب فارسی، واحد و بازه مجاز.
 *
 * بازه مجاز برای گرفتن اشتباه تایپی است، نه برای قضاوت درباره وضعیت مواجهه؛
 * خارج از بازه یعنی «این عدد از یک ابزار اندازه‌گیری واقعی نیامده».
 */
final readonly class InputDefinition
{
    private function __construct(
        public string $key,
        public string $label,
        public Unit $unit,
        public float $min,
        public float $max,
        public bool $list,
        public int $minItems,
        public int $maxItems,
    ) {}

    public static function single(string $key, string $label, Unit $unit, float $min, float $max): self
    {
        return new self($key, $label, $unit, $min, $max, false, 1, 1);
    }

    public static function list(
        string $key,
        string $label,
        Unit $unit,
        float $min,
        float $max,
        int $minItems,
        int $maxItems,
    ): self {
        return new self($key, $label, $unit, $min, $max, true, $minItems, $maxItems);
    }

    /**
     * شکل قراردادی ورودی — بدون برچسب.
     *
     * برچسب عمداً نیست: اثر انگشت رفتاری فرمول از همین آرایه ساخته می‌شود و
     * ویرایش متن فارسی یک برچسب نباید فرمول را وادار به گرفتن نسخه تازه کند.
     *
     * @return array<string, bool|float|int|string>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'unit' => $this->unit->value,
            'min' => $this->min,
            'max' => $this->max,
            'list' => $this->list,
            'min_items' => $this->minItems,
            'max_items' => $this->maxItems,
        ];
    }
}
