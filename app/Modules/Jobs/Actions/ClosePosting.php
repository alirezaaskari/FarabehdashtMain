<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Actions;

use App\Models\User;
use App\Modules\Jobs\Domain\Enums\PostingState;
use App\Modules\Jobs\Domain\JobPosting;
use App\Modules\Jobs\Events\PostingClosed;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * کارفرما آگهی پرشده را می‌بندد. از فهرست بیرون می‌رود، صفحه‌اش با برچسب
 * «بسته‌شده» و noindex می‌ماند و پس از مهلت DEC-66 ۴۱۰ می‌شود.
 */
final readonly class ClosePosting
{
    public function __construct(private Dispatcher $events) {}

    public function handle(User $user, JobPosting $posting): JobPosting
    {
        if ($posting->company->user_id !== $user->getKey()) {
            throw new RuntimeException('این آگهی مال شما نیست.');
        }

        if ($posting->state() === PostingState::Closed) {
            return $posting;
        }

        $posting->forceFill(['closed_at' => Carbon::now(), 'pending' => null])->save();

        $this->events->dispatch(new PostingClosed($posting, (int) $user->getKey()));

        return $posting;
    }
}
