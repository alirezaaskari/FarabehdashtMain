<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Actions;

use App\Contracts\Taxonomy;
use App\Modules\Jobs\Domain\Enums\EntryKind;
use App\Modules\Jobs\Domain\Passport;
use App\Modules\Jobs\Domain\PassportEntry;
use App\Modules\Jobs\Events\PassportUpdated;
use App\Modules\Jobs\Services\JobCatalog;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use RuntimeException;

/**
 * بخش «به اظهار خود کاربر» گذرنامه: معرفی کوتاه، شهر، سال‌های سابقه،
 * مهارت‌های اظهاری، سطرهای تحصیلات و سابقه و گواهی، و روشن یا خاموش بودن
 * صفحه اشتراکی (DEC-69).
 */
final readonly class SavePassport
{
    public function __construct(
        private Taxonomy $taxonomy,
        private Repository $config,
        private Dispatcher $events,
    ) {}

    /** @param  list<int>  $skillIds */
    public function profile(int $userId, ?string $headline, ?string $province, ?string $city, ?int $experienceYears, array $skillIds): Passport
    {
        $passport = Passport::of($userId);
        $passport->fill([
            'headline' => $headline === null || trim($headline) === '' ? null : trim($headline),
            'province' => $province,
            'city' => $city,
            'experience_years' => $experienceYears,
        ])->save();

        $this->taxonomy->sync(Passport::class, $passport->id, JobCatalog::TAXONOMY, $skillIds);
        $this->events->dispatch(new PassportUpdated($passport, 'profile'));

        return $passport;
    }

    public function share(int $userId, bool $shared): Passport
    {
        $passport = Passport::of($userId);

        if ($passport->shared !== $shared) {
            $passport->forceFill(['shared' => $shared])->save();
            $this->events->dispatch(new PassportUpdated($passport, $shared ? 'shared' : 'unshared'));
        }

        return $passport;
    }

    public function addEntry(int $userId, EntryKind $kind, string $title, ?string $organization, ?int $startYear, ?int $endYear, ?string $note): PassportEntry
    {
        $passport = Passport::of($userId);
        $max = (int) $this->config->get('jobs.passport.entries_max', 30);

        if ($passport->entries()->count() >= $max) {
            throw new RuntimeException('بیش از '.$max.' سطر نمی‌شود افزود؛ سطرهای قدیمی‌تر را بردارید.');
        }

        if ($startYear !== null && $endYear !== null && $endYear < $startYear) {
            throw new RuntimeException('سال پایان از سال شروع کمتر است.');
        }

        $entry = PassportEntry::query()->create([
            'passport_id' => $passport->id,
            'kind' => $kind,
            'title' => trim($title),
            'organization' => $organization === null || trim($organization) === '' ? null : trim($organization),
            'start_year' => $startYear,
            'end_year' => $endYear,
            'note' => $note === null || trim($note) === '' ? null : trim($note),
        ]);

        $this->events->dispatch(new PassportUpdated($passport, 'entry_added'));

        return $entry;
    }

    public function removeEntry(int $userId, int $entryId): void
    {
        $passport = Passport::of($userId);
        $entry = $passport->entries()->whereKey($entryId)->first() ?? throw new RuntimeException('این سطر پیدا نشد.');

        $entry->delete();
        $this->events->dispatch(new PassportUpdated($passport, 'entry_removed'));
    }
}
