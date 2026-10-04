<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Actions;

use App\Modules\Marketplace\Domain\Enums\ContractStatus;
use App\Modules\Marketplace\Domain\Enums\RatingSide;
use App\Modules\Marketplace\Domain\MarketContract;
use App\Modules\Marketplace\Domain\MarketRating;
use App\Modules\Marketplace\Events\ContractRated;
use App\Modules\Marketplace\Events\RatingModerated;
use App\Modules\Marketplace\Services\MessagePolicy;
use App\Support\PersianNumber;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * امتیاز دوطرفه پس از پایان قرارداد (بخش ۲۱-۶، DEC-85).
 *
 * فقط قراردادی که پولی از آن واقعاً جابه‌جا شده امتیازپذیر است: تمام‌شده،
 * یا لغوشده پس از پرداخت دست‌کم یک مرحله. هر طرف یک بار و در مهلت امتیاز
 * می‌دهد و امتیاز ثبت‌شده ویرایش نمی‌شود.
 */
final readonly class RateContract
{
    public function __construct(
        private MessagePolicy $policy,
        private Repository $config,
        private Dispatcher $events,
    ) {}

    public function handle(MarketContract $contract, int $userId, int $stars, ?string $comment): MarketRating
    {
        if (! $contract->involves($userId)) {
            throw new RuntimeException('فقط دو طرف قرارداد امتیاز می‌دهند.');
        }

        if (! $this->isOpen($contract)) {
            throw new RuntimeException('مهلت امتیازدادن به این قرارداد باز نیست.');
        }

        if ($stars < 1 || $stars > 5) {
            throw new RuntimeException('امتیاز را از ۱ تا ۵ انتخاب کنید.');
        }

        $comment = trim((string) $comment);
        $max = (int) $this->config->get('marketplace.ratings.comment_max', 280);

        if (mb_strlen($comment) > $max) {
            throw new RuntimeException('جمله امتیاز حداکثر '.PersianNumber::format($max).' نویسه است.');
        }

        if ($comment !== '' && $this->policy->flags($comment) !== []) {
            throw new RuntimeException('در جمله امتیاز شماره، ایمیل، لینک یا نام پیام‌رسان ننویسید.');
        }

        $isClient = $contract->client_user_id === $userId;

        try {
            $rating = MarketRating::query()->create([
                'contract_id' => $contract->id,
                'rater_user_id' => $userId,
                'ratee_user_id' => $isClient ? $contract->provider_user_id : $contract->client_user_id,
                'side' => $isClient ? RatingSide::Client : RatingSide::Provider,
                'stars' => $stars,
                'comment' => $comment === '' ? null : $comment,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new RuntimeException('امتیاز شما برای این قرارداد قبلاً ثبت شده است.');
        }

        $rating->setRelation('contract', $contract);
        $this->events->dispatch(new ContractRated($rating));

        return $rating;
    }

    public function moderate(MarketRating $rating, int $adminId, bool $hidden): void
    {
        $rating->forceFill([
            'hidden_at' => $hidden ? Carbon::now() : null,
            'hidden_by' => $hidden ? $adminId : null,
        ])->save();

        $this->events->dispatch(new RatingModerated($rating, $adminId, $hidden));
    }

    /** قرارداد پایان‌یافته‌ای که پولی از آن جابه‌جا شده و مهلت امتیازش نگذشته. */
    public function isOpen(MarketContract $contract): bool
    {
        $deadline = $this->deadline($contract);

        return $deadline !== null && Carbon::now()->lessThanOrEqualTo($deadline);
    }

    public function deadline(MarketContract $contract): ?Carbon
    {
        $ended = match ($contract->status) {
            ContractStatus::Completed => $contract->completed_at,
            ContractStatus::Cancelled => $contract->milestones()->whereNotNull('funded_at')->exists() ? $contract->cancelled_at : null,
            default => null,
        };

        return $ended?->copy()->addDays((int) $this->config->get('marketplace.ratings.window_days', 30));
    }
}
