<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Services;

use App\Contracts\MediaLibrary;
use App\Contracts\Taxonomy;
use App\Modules\Jobs\Domain\Company;
use App\Modules\Jobs\Domain\Enums\EmploymentType;
use App\Modules\Jobs\Domain\JobPosting;
use App\Support\Media\MediaData;
use App\Support\PersianNumber;
use App\Support\Regions\Regions;
use App\Support\Taxonomy\TermData;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

/**
 * فهرست آگهی‌ها: پالایش شهر، مهارت و نوع همکاری، شمارش برای صفحه‌های شهر و
 * مهارت و قاعده ایندکس (مثل DEC-59)، و برچسب‌های نمایشی.
 *
 * مهارت‌ها برچسب‌های دسته‌بندی «مهارت شغلی» Core‌اند (DEC-71) و از قرارداد
 * `Taxonomy` خوانده می‌شوند؛ شهر از config/regions.php.
 */
final readonly class JobCatalog
{
    public const TAXONOMY = 'job_skill';

    public function __construct(
        private Taxonomy $taxonomy,
        private Regions $regions,
        private MediaLibrary $media,
        private Repository $config,
    ) {}

    /** @return list<TermData> */
    public function skills(): array
    {
        try {
            return $this->taxonomy->terms(self::TAXONOMY);
        } catch (InvalidArgumentException) {
            return [];
        }
    }

    public function skill(?string $slug): ?TermData
    {
        foreach ($this->skills() as $term) {
            if ($slug !== null && $term->slug === $slug) {
                return $term;
            }
        }

        return null;
    }

    /** @return list<TermData> */
    public function skillsOf(JobPosting $posting): array
    {
        return $this->taxonomy->termsOf(JobPosting::class, $posting->id, self::TAXONOMY);
    }

    /** @return list<int> */
    public function skillIdsOf(JobPosting $posting): array
    {
        return array_map(static fn (TermData $term): int => $term->id, $this->skillsOf($posting));
    }

    /** @return Builder<JobPosting> */
    public function live(?string $city = null, ?string $skill = null, ?EmploymentType $type = null): Builder
    {
        return JobPosting::query()
            ->live()
            ->with('company')
            ->when($city !== null, static fn (Builder $query) => $query->where('city', $city))
            ->when($type !== null, static fn (Builder $query) => $query->where('employment_type', $type))
            ->when($skill !== null, fn (Builder $query) => $query->whereIn('id', $this->taxonomy->taggedIds(JobPosting::class, self::TAXONOMY, (string) $skill)));
    }

    /** DEC-67: آگهی بی حقوق پایین‌تر نمی‌رود؛ ترتیب فقط تازگی است. */
    public function perPage(): int
    {
        return (int) $this->config->get('jobs.per_page', 20);
    }

    /** صفحه شهر یا مهارت با کمتر از دو آگهی زنده کم‌محتواست و ایندکس نمی‌شود. */
    public function isIndexable(int $postings): bool
    {
        return $postings >= (int) $this->config->get('jobs.index_min_postings', 2);
    }

    /** @return array<string, int> کلید شهر => تعداد آگهی زنده */
    public function cityCounts(): array
    {
        $counts = [];

        foreach (JobPosting::query()->live()->whereNotNull('city')->pluck('city') as $city) {
            $counts[(string) $city] = ($counts[(string) $city] ?? 0) + 1;
        }

        arsort($counts);

        return $counts;
    }

    /** @return array<string, int> نامک مهارت => تعداد آگهی زنده */
    public function skillCounts(): array
    {
        $live = JobPosting::query()->live()->pluck('id')->all();
        $counts = [];

        if ($live === []) {
            return [];
        }

        foreach ($this->skills() as $term) {
            $count = count(array_intersect($live, $this->taxonomy->taggedIds(JobPosting::class, self::TAXONOMY, $term->slug)));

            if ($count > 0) {
                $counts[$term->slug] = $count;
            }
        }

        arsort($counts);

        return $counts;
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

    /** @return array<string, array{name: string, cities: array<string, string>}> کلید استان => … برای فرم */
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

    public function place(?string $province, ?string $city): string
    {
        $names = array_filter([$this->regions->cityName($city), $this->regions->provinceName($province)]);

        return implode('، ', array_unique($names));
    }

    public function experienceLabel(?int $years): string
    {
        return (int) $years === 0 ? 'بدون نیاز به سابقه' : 'دست‌کم '.PersianNumber::format((int) $years).' سال سابقه';
    }

    /** DEC-67: بی حقوق «توافقی». */
    public function salaryLabel(JobPosting $posting): string
    {
        $min = $posting->salaryMin();
        $max = $posting->salaryMax();

        return match (true) {
            $min !== null && $max !== null && ! $min->equals($max) => $min->formatWithoutUnit().' تا '.$max->format().' در ماه',
            $min !== null => ($max === null ? 'از ' : '').$min->format().' در ماه',
            $max !== null => 'تا '.$max->format().' در ماه',
            default => 'توافقی',
        };
    }

    public function logo(?int $id): ?MediaData
    {
        return $id === null ? null : $this->media->find($id);
    }

    /**
     * @param  iterable<Company>  $companies
     * @return array<int, MediaData>
     */
    public function logos(iterable $companies): array
    {
        $ids = [];

        foreach ($companies as $company) {
            if ($company->logo_id !== null) {
                $ids[] = $company->logo_id;
            }
        }

        return $ids === [] ? [] : $this->media->findMany(array_values(array_unique($ids)));
    }
}
