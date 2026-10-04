<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Services;

use App\Contracts\ProjectTrackRecord;
use App\Modules\Marketplace\Domain\Enums\ContractStatus;
use App\Modules\Marketplace\Domain\Enums\RatingSide;
use App\Modules\Marketplace\Domain\MarketContract;
use App\Modules\Marketplace\Domain\MarketRating;
use App\Support\Market\ProviderRecord;
use App\Support\Market\RatingQuote;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Carbon;

/**
 * سابقه مجری و کارفرما در بازار پروژه (بخش ۲۱-۶).
 *
 * فقط قرارداد واقعی شمرده می‌شود: «تحویل‌شده» یعنی قرارداد تمام‌شده و
 * امتیاز فقط وقتی در میانگین می‌آید که دیده‌شدنی باشد (DEC-85).
 */
final readonly class TrackRecord implements ProjectTrackRecord
{
    public function __construct(private Repository $config) {}

    public function ofProviders(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $completed = MarketContract::query()
            ->whereIn('provider_user_id', $userIds)
            ->where('status', ContractStatus::Completed)
            ->selectRaw('provider_user_id, count(*) as aggregate')
            ->groupBy('provider_user_id')
            ->pluck('aggregate', 'provider_user_id');

        $ratings = MarketRating::query()
            ->whereIn('ratee_user_id', $userIds)
            ->where('side', RatingSide::Client)
            ->revealed($this->revealCutoff())
            ->latest('id')
            ->get()
            ->groupBy('ratee_user_id');

        $min = $this->averageMin();
        $quotes = (int) $this->config->get('marketplace.ratings.quotes', 3);
        $records = [];

        foreach ($userIds as $userId) {
            $own = $ratings->get($userId) ?? collect();
            $records[$userId] = new ProviderRecord(
                completed: (int) ($completed[$userId] ?? 0),
                ratings: $own->count(),
                average: $own->count() >= $min ? round((float) $own->avg('stars'), 1) : null,
                averageMin: $min,
                quotes: $own
                    ->filter(static fn (MarketRating $rating): bool => $rating->visibleComment() !== null)
                    ->take($quotes)
                    ->map(static fn (MarketRating $rating): RatingQuote => new RatingQuote($rating->stars, (string) $rating->visibleComment(), $rating->created_at))
                    ->values()
                    ->all(),
            );
        }

        return $records;
    }

    /** کارفرمایی که چند پروژه را تا آخر پرداخته؛ نشان داخلی است، نه مجوز. */
    public function isTrustedClient(int $userId): bool
    {
        return MarketContract::query()
            ->where('client_user_id', $userId)
            ->where('status', ContractStatus::Completed)
            ->count() >= (int) $this->config->get('marketplace.ratings.trusted_client_min', 3);
    }

    public function revealCutoff(): Carbon
    {
        return Carbon::now()->subDays((int) $this->config->get('marketplace.ratings.reveal_days', 14));
    }

    public function averageMin(): int
    {
        return max(1, (int) $this->config->get('marketplace.ratings.average_min', 3));
    }
}
