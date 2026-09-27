<?php

declare(strict_types=1);

namespace App\Support\Regions;

use Illuminate\Contracts\Config\Repository;

/**
 * استان و شهر از فهرست ثابت `config/regions.php` (DEC-60).
 *
 * نشانی صفحه‌ها کلید لاتین می‌گیرد و متن کاربر نام فارسی؛ هر ماژولی که
 * مکان ذخیره می‌کند فقط کلید نگه می‌دارد و نام را از این‌جا می‌خواند.
 */
final readonly class Regions
{
    public function __construct(private Repository $config) {}

    /** @return array<string, string> کلید استان => نام */
    public function provinces(): array
    {
        return array_map(static fn (array $province): string => $province['name'], $this->all());
    }

    /** @return array<string, string> کلید شهر => نام */
    public function cities(string $province): array
    {
        return $this->all()[$province]['cities'] ?? [];
    }

    public function provinceName(?string $province): ?string
    {
        return $province === null ? null : ($this->all()[$province]['name'] ?? null);
    }

    public function cityName(?string $city): ?string
    {
        foreach ($this->all() as $province) {
            if ($city !== null && isset($province['cities'][$city])) {
                return $province['cities'][$city];
            }
        }

        return null;
    }

    public function contains(string $province, string $city): bool
    {
        return isset($this->all()[$province]['cities'][$city]);
    }

    /** @return array<string, array{name: string, cities: array<string, string>}> */
    private function all(): array
    {
        /** @var array<string, array{name: string, cities: array<string, string>}> */
        return (array) $this->config->get('regions', []);
    }
}
