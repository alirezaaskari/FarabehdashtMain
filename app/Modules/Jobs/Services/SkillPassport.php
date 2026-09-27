<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Services;

use App\Contracts\PassportEvidenceSource;
use App\Contracts\Taxonomy;
use App\Modules\Jobs\Domain\Passport;
use App\Support\Passport\PassportEvidence;
use App\Support\Taxonomy\TermData;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;

/**
 * دو بخش گذرنامه مهارتی (۲۰-۳) و مهارت‌هایی که از هر کدام می‌آید.
 *
 * «ثبت‌شده در فرابهداشت» هر بار از منبع‌های ماژول‌ها خوانده می‌شود و
 * برچسب‌هایش با جدول `jobs.passport.tag_skills` مهارت ثبت‌شده می‌سازد.
 * «به اظهار خود کاربر» سطرها و مهارت‌هایی است که خودش انتخاب کرده؛ هیچ‌کدام
 * نشان تأیید ندارند. تطبیق با آگهی (۲۰-۴) از {@see skillIds()} می‌خواند.
 */
final readonly class SkillPassport
{
    public function __construct(
        private Container $container,
        private Taxonomy $taxonomy,
        private JobCatalog $catalog,
        private JobPricing $pricing,
        private Repository $config,
    ) {}

    /**
     * بخش‌های «ثبت‌شده»، هر منبع با سطرهای خودش؛ منبع خالی نمی‌آید.
     *
     * @return list<array{key: string, label: string, items: list<PassportEvidence>}>
     */
    public function verified(int $userId): array
    {
        $sections = [];
        $minScore = $this->pricing->examMinPercent();

        /** @var iterable<PassportEvidenceSource> $sources */
        $sources = $this->container->tagged(PassportEvidenceSource::TAG);

        foreach ($sources as $source) {
            $items = array_values(array_filter(
                $source->evidence($userId),
                static fn (PassportEvidence $item): bool => $item->score === null || $item->score >= $minScore,
            ));

            if ($items !== []) {
                $sections[] = ['key' => $source->key(), 'label' => $source->label(), 'items' => $items];
            }
        }

        return $sections;
    }

    /**
     * مهارت‌هایی که پشتوانه ثبت‌شده دارند.
     *
     * @param  list<array{key: string, label: string, items: list<PassportEvidence>}>|null  $verified
     * @return list<TermData>
     */
    public function verifiedSkills(int $userId, ?array $verified = null): array
    {
        $map = (array) $this->config->get('jobs.passport.tag_skills', []);
        $slugs = [];

        foreach ($verified ?? $this->verified($userId) as $section) {
            foreach ($section['items'] as $item) {
                foreach ($item->tags as $tag) {
                    if (isset($map[$tag])) {
                        $slugs[(string) $map[$tag]] = true;
                    }
                }
            }
        }

        return array_values(array_filter($this->catalog->skills(), static fn (TermData $term): bool => isset($slugs[$term->slug])));
    }

    /** @return list<TermData> */
    public function declaredSkills(Passport $passport): array
    {
        return $this->taxonomy->termsOf(Passport::class, $passport->id, JobCatalog::TAXONOMY);
    }

    /**
     * همه مهارت‌های کاربر برای تطبیق: ثبت‌شده و اظهاری با هم.
     *
     * @return list<int>
     */
    public function skillIds(int $userId): array
    {
        $passport = Passport::query()->where('user_id', $userId)->first();
        $terms = [...$this->verifiedSkills($userId), ...($passport === null ? [] : $this->declaredSkills($passport))];

        return array_values(array_unique(array_map(static fn (TermData $term): int => $term->id, $terms)));
    }
}
