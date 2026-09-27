<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Actions;

use App\Contracts\Taxonomy;
use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Modules\Consulting\Domain\Enums\ProfileReviewStatus;
use App\Modules\Consulting\Domain\ProfileDraft;
use App\Modules\Consulting\Events\ConsultantProfilePublished;
use App\Modules\Consulting\Events\ConsultantProfileRejected;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * تصمیم مدیر درباره ویرایش در انتظار صفحه مشاور. برگرداندن بدون یادداشت
 * پذیرفته نیست؛ مشاور باید بداند چه چیزی را اصلاح کند.
 */
final readonly class ReviewConsultantProfile
{
    public const TAXONOMY = 'health_domain';

    public function __construct(
        private Taxonomy $taxonomy,
        private DatabaseManager $db,
        private Dispatcher $events,
    ) {}

    public function approve(ConsultantProfile $profile, int $adminId): ConsultantProfile
    {
        $draft = $this->pendingDraft($profile);

        $this->db->transaction(function () use ($profile, $draft, $adminId): void {
            $profile->forceFill([
                'slug' => $draft->slug,
                'display_name' => $draft->displayName,
                'headline' => $draft->headline,
                'bio' => $draft->bio,
                'province' => $draft->province,
                'city' => $draft->city,
                'experience' => $draft->experience,
                'education' => $draft->education,
                'photo_id' => $draft->photoId,
                'offerings' => $draft->offerings,
                'published_at' => $profile->published_at ?? Carbon::now(),
                'pending' => null,
                'status' => ProfileReviewStatus::Approved,
                'review_note' => null,
                'reviewed_by' => $adminId,
                'reviewed_at' => Carbon::now(),
            ])->save();

            $this->taxonomy->sync(ConsultantProfile::class, $profile->id, self::TAXONOMY, $draft->domainIds);
        });

        $this->events->dispatch(new ConsultantProfilePublished($profile, $adminId));

        return $profile;
    }

    public function reject(ConsultantProfile $profile, int $adminId, string $note): ConsultantProfile
    {
        $note = trim($note);

        if ($note === '') {
            throw new RuntimeException('یادداشتی بنویسید که مشاور بداند چه چیزی را اصلاح کند.');
        }

        $this->pendingDraft($profile);

        $profile->forceFill([
            'status' => ProfileReviewStatus::Rejected,
            'review_note' => $note,
            'reviewed_by' => $adminId,
            'reviewed_at' => Carbon::now(),
        ])->save();

        $this->events->dispatch(new ConsultantProfileRejected($profile, $adminId));

        return $profile;
    }

    private function pendingDraft(ConsultantProfile $profile): ProfileDraft
    {
        if ($profile->status !== ProfileReviewStatus::Pending || $profile->pending === null) {
            throw new RuntimeException('این صفحه ویرایشی در انتظار تأیید ندارد.');
        }

        return ProfileDraft::fromArray($profile->pending);
    }
}
