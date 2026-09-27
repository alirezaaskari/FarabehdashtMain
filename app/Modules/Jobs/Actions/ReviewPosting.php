<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Actions;

use App\Contracts\Taxonomy;
use App\Modules\Jobs\Domain\Enums\ReviewStatus;
use App\Modules\Jobs\Domain\JobPosting;
use App\Modules\Jobs\Domain\PostingDraft;
use App\Modules\Jobs\Events\PostingReviewed;
use App\Modules\Jobs\Services\JobCatalog;
use App\Modules\Jobs\Services\JobPricing;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * تصمیم مدیر درباره آگهی یا ویرایش آگهی.
 *
 * نخستین تأیید آگهی اگر دوره‌اش رایگان باشد (DEC-63) همان لحظه منتشرش
 * می‌کند؛ وگرنه آگهی «آماده پرداخت» می‌شود. تأیید ویرایش آگهی زنده فقط متن را
 * عوض می‌کند و به اعتبارش دست نمی‌زند.
 */
final readonly class ReviewPosting
{
    public function __construct(
        private Taxonomy $taxonomy,
        private PostingCheckout $checkout,
        private JobPricing $pricing,
        private DatabaseManager $db,
        private Dispatcher $events,
    ) {}

    public function approve(JobPosting $posting, int $adminId): JobPosting
    {
        $draft = $this->pendingDraft($posting);

        $this->db->transaction(function () use ($posting, $draft, $adminId): void {
            $posting->forceFill([
                'title' => $draft->title,
                'province' => $draft->province,
                'city' => $draft->city,
                'employment_type' => $draft->employmentType,
                'min_experience_years' => $draft->minExperienceYears,
                'salary_min_toman' => $draft->salaryMinToman,
                'salary_max_toman' => $draft->salaryMaxToman,
                'description' => $draft->description,
                'approved_at' => $posting->approved_at ?? Carbon::now(),
                'pending' => null,
                'status' => ReviewStatus::Approved,
                'review_note' => null,
                'reviewed_by' => $adminId,
                'reviewed_at' => Carbon::now(),
            ])->save();

            $this->taxonomy->sync(JobPosting::class, $posting->id, JobCatalog::TAXONOMY, $draft->skillIds);
        });

        if ($posting->published_at === null && $this->pricing->priceFor($posting->company)->isZero()) {
            $this->checkout->publishFree($this->checkout->open($posting), $adminId);
            $posting->refresh();
        }

        $this->events->dispatch(new PostingReviewed($posting, $adminId, approved: true, recipientId: $posting->company->user_id));

        return $posting;
    }

    public function reject(JobPosting $posting, int $adminId, string $note): JobPosting
    {
        $note = trim($note);

        if ($note === '') {
            throw new RuntimeException('یادداشتی بنویسید که کارفرما بداند چه چیزی را اصلاح کند.');
        }

        $this->pendingDraft($posting);

        $posting->forceFill([
            'status' => ReviewStatus::Rejected,
            'review_note' => $note,
            'reviewed_by' => $adminId,
            'reviewed_at' => Carbon::now(),
        ])->save();

        $this->events->dispatch(new PostingReviewed($posting, $adminId, approved: false, recipientId: $posting->company->user_id));

        return $posting;
    }

    private function pendingDraft(JobPosting $posting): PostingDraft
    {
        if ($posting->status !== ReviewStatus::Pending || $posting->pending === null) {
            throw new RuntimeException('این آگهی ویرایشی در انتظار تأیید ندارد.');
        }

        return PostingDraft::fromArray($posting->pending);
    }
}
