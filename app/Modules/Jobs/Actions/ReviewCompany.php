<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Actions;

use App\Modules\Jobs\Domain\Company;
use App\Modules\Jobs\Domain\CompanyDraft;
use App\Modules\Jobs\Domain\Enums\CompanySize;
use App\Modules\Jobs\Domain\Enums\ReviewStatus;
use App\Modules\Jobs\Events\CompanyReviewed;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * تصمیم مدیر درباره ویرایش در انتظار صفحه شرکت. برگرداندن بی یادداشت
 * پذیرفته نیست؛ کارفرما باید بداند چه چیزی را اصلاح کند.
 */
final readonly class ReviewCompany
{
    public function __construct(private Dispatcher $events) {}

    public function approve(Company $company, int $adminId): Company
    {
        $draft = $this->pendingDraft($company);

        $company->forceFill([
            'slug' => $draft->slug,
            'name' => $draft->name,
            'industry' => $draft->industry,
            'size' => CompanySize::tryFrom($draft->size),
            'province' => $draft->province,
            'city' => $draft->city,
            'about' => $draft->about,
            'logo_id' => $draft->logoId,
            'published_at' => $company->published_at ?? Carbon::now(),
            'pending' => null,
            'status' => ReviewStatus::Approved,
            'review_note' => null,
            'reviewed_by' => $adminId,
            'reviewed_at' => Carbon::now(),
        ])->save();

        $this->events->dispatch(new CompanyReviewed($company, $adminId, approved: true));

        return $company;
    }

    public function reject(Company $company, int $adminId, string $note): Company
    {
        $note = trim($note);

        if ($note === '') {
            throw new RuntimeException('یادداشتی بنویسید که کارفرما بداند چه چیزی را اصلاح کند.');
        }

        $this->pendingDraft($company);

        $company->forceFill([
            'status' => ReviewStatus::Rejected,
            'review_note' => $note,
            'reviewed_by' => $adminId,
            'reviewed_at' => Carbon::now(),
        ])->save();

        $this->events->dispatch(new CompanyReviewed($company, $adminId, approved: false));

        return $company;
    }

    private function pendingDraft(Company $company): CompanyDraft
    {
        if ($company->status !== ReviewStatus::Pending || $company->pending === null) {
            throw new RuntimeException('این صفحه ویرایشی در انتظار تأیید ندارد.');
        }

        return CompanyDraft::fromArray($company->pending);
    }
}
