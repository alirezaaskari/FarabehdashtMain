<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Services;

use App\Contracts\ServiceProviderDirectory;
use App\Modules\Marketplace\Domain\MarketProject;
use App\Support\Regions\Regions;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder;

/**
 * فهرست بازار پروژه: خدمت‌ها (DEC-87، از قرارداد دایرکتوری)، پالایش خدمت و
 * شهر، شمارش برای صفحه‌های ثابت و قاعده ایندکس (مثل DEC-59)، و برچسب‌های
 * نمایشی مثل «کارفرما در اصفهان» (DEC-84).
 */
final readonly class MarketCatalog
{
    public function __construct(
        private Container $container,
        private Regions $regions,
        private Repository $config,
    ) {}

    /** @return array<string, string> کلید => نام فارسی؛ بی ماژول مشاوره، خالی */
    public function services(): array
    {
        return $this->container->bound(ServiceProviderDirectory::class)
            ? $this->container->make(ServiceProviderDirectory::class)->services()
            : [];
    }

    public function serviceName(?string $key): ?string
    {
        return $key === null ? null : ($this->services()[$key] ?? null);
    }

    /** @return Builder<MarketProject> */
    public function listed(?string $service = null, ?string $city = null): Builder
    {
        return MarketProject::query()
            ->listed()
            ->when($service !== null, static fn (Builder $query) => $query->where('service', $service))
            ->when($city !== null, static fn (Builder $query) => $query->where('city', $city));
    }

    public function perPage(): int
    {
        return (int) $this->config->get('marketplace.per_page', 20);
    }

    public function isIndexable(int $projects): bool
    {
        return $projects >= (int) $this->config->get('marketplace.index_min_projects', 2);
    }

    /** @return array<string, int> کلید خدمت => تعداد پروژه باز */
    public function serviceCounts(): array
    {
        return $this->counts('service');
    }

    /** @return array<string, int> کلید شهر => تعداد پروژه باز */
    public function cityCounts(): array
    {
        return $this->counts('city');
    }

    /** @return array<string, array<string, string>> نام استان => [کلید شهر => نام] */
    public function citiesByProvince(): array
    {
        $grouped = [];

        foreach ($this->regions->provinces() as $key => $name) {
            $grouped[$name] = $this->regions->cities($key);
        }

        return $grouped;
    }

    /** @return array<string, array{name: string, cities: array<string, string>}> */
    public function regionsForForm(): array
    {
        $regions = [];

        foreach ($this->regions->provinces() as $key => $name) {
            $regions[$key] = ['name' => $name, 'cities' => $this->regions->cities($key)];
        }

        return $regions;
    }

    public function cityName(?string $city): ?string
    {
        return $this->regions->cityName($city);
    }

    public function place(MarketProject $project): string
    {
        if ($project->remote) {
            return 'از راه دور';
        }

        return implode('، ', array_unique(array_filter([
            $this->regions->cityName($project->city),
            $this->regions->provinceName($project->province),
        ])));
    }

    /** DEC-84: نام کارفرما فقط با انتخاب خودش؛ وگرنه «کارفرما در اصفهان». */
    public function clientLabel(MarketProject $project): string
    {
        if ($project->show_client_name && $project->client_name !== null) {
            return $project->client_name;
        }

        $city = $this->regions->cityName($project->city);

        return $city === null ? 'کارفرمای فرابهداشت' : 'کارفرما در '.$city;
    }

    public function budgetLabel(MarketProject $project): string
    {
        return $project->budget_min_toman === $project->budget_max_toman
            ? $project->budgetMax()->format()
            : $project->budgetMin()->formatWithoutUnit().' تا '.$project->budgetMax()->format();
    }

    /** @return array<string, int> */
    private function counts(string $column): array
    {
        $counts = [];

        foreach (MarketProject::query()->listed()->whereNotNull($column)->pluck($column) as $value) {
            $counts[(string) $value] = ($counts[(string) $value] ?? 0) + 1;
        }

        arsort($counts);

        return $counts;
    }
}
