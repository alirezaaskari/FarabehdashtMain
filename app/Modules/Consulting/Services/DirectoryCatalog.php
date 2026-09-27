<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Services;

use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Support\Regions\Regions;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;

/**
 * دایرکتوری خدمات تخصصی (بخش ۱۹-۵): فهرست ثابت خدمت‌ها، ارائه‌دهنده‌های هر
 * خدمت و شهر، و قاعده ایندکس (DEC-59).
 *
 * «شهر» یک ارائه‌دهنده شهر صفحه عمومی اوست؛ فهرست شهرها همان config/regions.php است.
 */
final readonly class DirectoryCatalog
{
    public function __construct(
        private Repository $config,
        private Regions $regions,
    ) {}

    /** @return array<string, string> کلید => نام فارسی */
    public function services(): array
    {
        return (array) $this->config->get('consulting.directory.services', []);
    }

    public function serviceName(?string $key): ?string
    {
        return $key === null ? null : ($this->services()[$key] ?? null);
    }

    /** @return Builder<ConsultantProfile> */
    public function providers(?string $service = null, ?string $city = null): Builder
    {
        return ConsultantProfile::query()
            ->listed()
            ->when($service !== null, static fn (Builder $query) => $query->whereJsonContains('offerings', $service))
            ->when($city !== null, static fn (Builder $query) => $query->where('city', $city));
    }

    /** DEC-59: صفحه با کمتر از دو ارائه‌دهنده منتشرشده کم‌محتواست و ایندکس نمی‌شود. */
    public function isIndexable(int $providers): bool
    {
        return $providers >= (int) $this->config->get('consulting.directory.index_min_providers', 2);
    }

    /**
     * ترکیب‌های خدمت و شهر که دست‌کم یک ارائه‌دهنده دارند، با شمارش؛ برای
     * پیوندهای صفحه خدمت و نقشه سایت.
     *
     * @return array<string, array<string, int>> خدمت => [شهر => تعداد]
     */
    public function coverage(): array
    {
        $coverage = [];
        $services = $this->services();

        foreach (ConsultantProfile::query()->listed()->whereNotNull('city')->get(['city', 'offerings']) as $profile) {
            foreach ($profile->offerings ?? [] as $service) {
                if (isset($services[$service])) {
                    $coverage[$service][(string) $profile->city] = ($coverage[$service][(string) $profile->city] ?? 0) + 1;
                }
            }
        }

        return $coverage;
    }

    /** @return array<string, array<string, string>> استان => [کلید شهر => نام] */
    public function citiesByProvince(): array
    {
        $grouped = [];

        foreach ($this->regions->provinces() as $key => $name) {
            $grouped[$name] = $this->regions->cities($key);
        }

        return $grouped;
    }

    public function cityName(?string $city): ?string
    {
        return $this->regions->cityName($city);
    }
}
