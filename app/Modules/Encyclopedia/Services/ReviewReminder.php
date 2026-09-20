<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Services;

use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\Enums\ArticleStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * یادآور بازبینی.
 *
 * فهرست محتوایی که موعد بازبینی‌اش گذشته یا نزدیک است، به ترتیب فوریت.
 * محتوای منتشرنشده در این فهرست نمی‌آید: پیش‌نویس هنوز موعدی ندارد و
 * قاطی‌کردنش با محتوای زنده، صف کار مدیر را بی‌اعتبار می‌کند.
 */
final readonly class ReviewReminder
{
    public function __construct(private int $horizonDays) {}

    /** @return Collection<int, Article> */
    public function due(?Carbon $now = null): Collection
    {
        $now ??= Carbon::now();

        return Article::query()
            ->where('status', ArticleStatus::Published->value)
            ->whereNotNull('review_due_at')
            ->where('review_due_at', '<=', $now->copy()->addDays($this->horizonDays))
            ->orderBy('review_due_at')
            ->with(['reviewer', 'sections', 'references', 'links'])
            ->get();
    }

    /** @return Collection<int, Article> */
    public function overdue(?Carbon $now = null): Collection
    {
        $now ??= Carbon::now();

        return Article::query()
            ->where('status', ArticleStatus::Published->value)
            ->whereNotNull('review_due_at')
            ->where('review_due_at', '<', $now)
            ->orderBy('review_due_at')
            ->get();
    }
}
