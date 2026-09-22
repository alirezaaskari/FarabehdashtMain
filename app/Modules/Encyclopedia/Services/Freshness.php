<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Services;

use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\Enums\Freshness as Level;
use Illuminate\Support\Carbon;

/**
 * نشانگر تازگی داده.
 *
 * موعد بازبینی هنگام انتشار محاسبه و ذخیره می‌شود، نه این‌جا. دلیلش این است
 * که دوره بازبینیِ یک نوع محتوا ممکن است فردا عوض شود، و آن‌وقت موعد صدها
 * محتوای منتشرشده بی‌خبر جابه‌جا می‌شد. این سرویس فقط **می‌خواند**.
 *
 * محتوای بدون موعد ثبت‌شده «نیازمند بازبینی» است و نه «تازه»: نبودِ داده هرگز
 * به سود محتوا تفسیر نمی‌شود.
 */
final readonly class Freshness
{
    public function __construct(private int $warningDays) {}

    public function of(Article $article, ?Carbon $now = null): Level
    {
        $now ??= Carbon::now();
        $due = $article->review_due_at;

        if ($due === null || ! $article->isReviewed()) {
            return Level::Stale;
        }

        if ($now->greaterThanOrEqualTo($due)) {
            return Level::Stale;
        }

        return $now->diffInDays($due, absolute: true) <= $this->warningDays
            ? Level::Aging
            : Level::Fresh;
    }

    /** چند روز تا موعد بازبینی — منفی یعنی گذشته، null یعنی موعدی ثبت نشده. */
    public function daysUntilDue(Article $article, ?Carbon $now = null): ?int
    {
        $due = $article->review_due_at;

        if ($due === null) {
            return null;
        }

        return (int) ($now ?? Carbon::now())->startOfDay()->diffInDays($due->startOfDay(), absolute: false);
    }
}
