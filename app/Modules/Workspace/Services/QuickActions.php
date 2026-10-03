<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Services;

use App\Contracts\QuickActionSource;
use App\Models\User;
use App\Support\Search\QuickAction;
use App\Support\Search\SearchQuery;

/**
 * کارهای پنل فرمان از همه ماژول‌های ثبت‌شده.
 *
 * بی عبارت، همه کارهای این کاربر به ترتیب ثبت ماژول‌ها می‌آید (هر ماژول
 * دو سه کار، پس فهرست کوتاه می‌ماند)؛ با عبارت، فقط آن‌هایی که همه کلمه‌ها
 * در عنوان یا کلیدواژه‌شان هست، تا سقف `$limit` تا جا برای نتایج بماند.
 */
final readonly class QuickActions
{
    /** @param  iterable<QuickActionSource>  $sources */
    public function __construct(private iterable $sources) {}

    /** @return list<QuickAction> */
    public function for(?User $user, SearchQuery $query, int $limit): array
    {
        $actions = [];

        foreach ($this->sources as $source) {
            foreach ($source->quickActions($user) as $action) {
                if ($query->raw === '' || $action->matches($query)) {
                    $actions[] = $action;
                }
            }
        }

        return $query->raw === '' ? $actions : array_slice($actions, 0, $limit);
    }
}
