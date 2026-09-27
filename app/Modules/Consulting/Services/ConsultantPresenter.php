<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Services;

use App\Contracts\MediaLibrary;
use App\Contracts\Taxonomy;
use App\Modules\Consulting\Actions\ReviewConsultantProfile;
use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Support\Media\MediaData;
use App\Support\Regions\Regions;
use App\Support\Taxonomy\TermData;
use Illuminate\Support\Collection;

/**
 * داده نمایشی صفحه‌های مشاور: عکس، حوزه‌ها و نام شهر، برای چند پروفایل با
 * یک پرس‌وجو به ازای هر نوع.
 */
final readonly class ConsultantPresenter
{
    public function __construct(
        private MediaLibrary $media,
        private Taxonomy $taxonomy,
        private Regions $regions,
    ) {}

    /** @return list<TermData> */
    public function domainTerms(): array
    {
        return $this->taxonomy->terms(ReviewConsultantProfile::TAXONOMY);
    }

    /** @return list<TermData> */
    public function domainsOf(ConsultantProfile $profile): array
    {
        return $this->taxonomy->termsOf(ConsultantProfile::class, $profile->id, ReviewConsultantProfile::TAXONOMY);
    }

    /**
     * @param  Collection<int, ConsultantProfile>|iterable<ConsultantProfile>  $profiles
     * @return array<int, MediaData> کلید: شناسه تصویر
     */
    public function photos(iterable $profiles): array
    {
        $ids = [];

        foreach ($profiles as $profile) {
            if ($profile->photo_id !== null) {
                $ids[] = $profile->photo_id;
            }
        }

        return $ids === [] ? [] : $this->media->findMany(array_values(array_unique($ids)));
    }

    public function photo(?int $id): ?MediaData
    {
        return $id === null ? null : $this->media->find($id);
    }

    public function place(?string $province, ?string $city): string
    {
        $names = array_filter([$this->regions->cityName($city), $this->regions->provinceName($province)]);

        // شهری که هم‌نام استانش است (تهران، تهران) یک بار نوشته می‌شود.
        return implode('، ', array_unique($names));
    }
}
