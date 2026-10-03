<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Services;

use App\Contracts\ServiceProviderDirectory;
use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Modules\Consulting\Domain\Enums\ProviderKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

/**
 * خدمت‌ها و ارائه‌دهنده‌های دایرکتوری برای بازار پروژه (بخش ۲۱).
 */
final readonly class DirectoryProviders implements ServiceProviderDirectory
{
    public function __construct(private DirectoryCatalog $catalog) {}

    public function services(): array
    {
        return $this->catalog->services();
    }

    public function providersOf(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return ConsultantProfile::query()
            ->listed()
            ->whereIn('user_id', $userIds)
            ->get(['user_id', 'slug', 'display_name', 'kind', 'city'])
            ->mapWithKeys(static function (ConsultantProfile $profile): array {
                $laboratory = $profile->kind === ProviderKind::Laboratory;
                $route = $laboratory ? 'consulting.labs.show' : 'consulting.show';

                return [$profile->user_id => [
                    'name' => (string) $profile->display_name,
                    'url' => Route::has($route) ? route($route, $profile->slug) : '',
                    'laboratory' => $laboratory,
                    'city' => $profile->city,
                ]];
            })
            ->all();
    }

    public function matching(string $service, ?string $city): array
    {
        return ConsultantProfile::query()
            ->listed()
            ->where(static fn (Builder $query) => $query
                ->whereJsonContains('offerings', $service)
                ->when($city !== null, static fn (Builder $query) => $query->orWhere('city', $city)))
            ->pluck('user_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
