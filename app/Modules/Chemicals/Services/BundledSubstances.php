<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Services;

use App\Modules\Chemicals\Actions\SaveSubstance;
use JsonException;
use RuntimeException;

/**
 * بانک داده اولیه‌ای که همراه کد می‌آید: `database/data/substances.json`.
 *
 * هر ردیف همان شکلی را دارد که {@see SaveSubstance}
 * از فرم پنل می‌گیرد، به‌اضافه حدود مواجهه با منبع و فهرست منابع ماده. داده
 * از متن مراجع رونویسی شده و منبع هر عدد کنارش است؛ چیزی حدس زده نشده.
 * چگونگی ساختش در README ماژول آمده است.
 */
final readonly class BundledSubstances
{
    public function __construct(private string $path) {}

    /**
     * @return list<array<string, mixed>>
     *
     * @throws RuntimeException اگر فایل نباشد یا JSON معتبر نباشد
     */
    public function all(): array
    {
        if (! is_file($this->path)) {
            throw new RuntimeException("فایل داده اولیه بانک مواد پیدا نشد: {$this->path}");
        }

        try {
            $data = json_decode((string) file_get_contents($this->path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('فایل داده اولیه بانک مواد JSON معتبر نیست: '.$exception->getMessage(), previous: $exception);
        }

        $substances = is_array($data) && is_array($data['substances'] ?? null) ? $data['substances'] : [];

        return array_values(array_filter($substances, is_array(...)));
    }
}
