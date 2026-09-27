<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Actions;

use App\Models\User;
use App\Modules\Jobs\Domain\Enums\AccessKind;
use App\Modules\Jobs\Domain\Enums\AccessSource;
use App\Modules\Jobs\Domain\Enums\ApplicationStatus;
use App\Modules\Jobs\Domain\JobApplication;
use App\Modules\Jobs\Events\ApplicationStatusChanged;
use App\Modules\Jobs\Services\ResumeAccessRecorder;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * کار کارفرما روی یک درخواست: باز کردن (خودکار «دیده‌شده»)، تصمیم، و دیدن
 * شماره یا رزومه که هر بار در دفتر دسترسی ثبت می‌شود (DEC-68).
 */
final readonly class ReviewApplication
{
    public function __construct(
        private ResumeAccessRecorder $access,
        private Dispatcher $events,
    ) {}

    public function open(User $employer, JobApplication $application): void
    {
        $this->authorize($employer, $application);

        if ($application->status === ApplicationStatus::Received) {
            $this->change($application, ApplicationStatus::Seen, (int) $employer->getKey());
        }
    }

    public function decide(User $employer, JobApplication $application, ApplicationStatus $status): void
    {
        $this->authorize($employer, $application);

        if (! in_array($status, ApplicationStatus::employerChoices(), true)) {
            throw new RuntimeException('این وضعیت را نمی‌شود دستی انتخاب کرد.');
        }

        if ($application->status === $status) {
            return;
        }

        $this->change($application, $status, (int) $employer->getKey());
    }

    /** شماره و ایمیل کارجو، فقط اگر برای همین درخواست اجازه داده باشد. */
    public function contact(User $employer, JobApplication $application): void
    {
        $this->authorize($employer, $application);

        if (! $application->share_contact) {
            throw new RuntimeException('کارجو اجازه نمایش شماره و ایمیلش را نداده است؛ از گفت‌وگوی همین درخواست پیام بدهید.');
        }

        $this->log($employer, $application, AccessKind::Contact);
    }

    /** مسیر فایل رزومه روی دیسک local، پس از ثبت در دفتر دسترسی. */
    public function resume(User $employer, JobApplication $application): string
    {
        $this->authorize($employer, $application);

        if ($application->resume_path === null) {
            throw new RuntimeException('رزومه این درخواست دیگر در دسترس نیست.');
        }

        $this->log($employer, $application, AccessKind::Resume);

        return $application->resume_path;
    }

    private function authorize(User $employer, JobApplication $application): void
    {
        if ($application->employerId() !== $employer->getKey()) {
            throw new RuntimeException('این درخواست برای آگهی شما نیست.');
        }

        if ($application->status === ApplicationStatus::Withdrawn) {
            throw new RuntimeException('کارجو این درخواست را پس گرفته است.');
        }
    }

    private function change(JobApplication $application, ApplicationStatus $status, int $actorId): void
    {
        $previous = $application->status;
        $application->forceFill(['status' => $status, 'status_changed_at' => Carbon::now()])->save();

        $this->events->dispatch(new ApplicationStatusChanged($application, $previous, $actorId));
    }

    private function log(User $employer, JobApplication $application, AccessKind $kind): void
    {
        $this->access->record(
            $application->user_id,
            (int) $employer->getKey(),
            $application->posting->company_id,
            AccessSource::Application,
            $kind,
            $application->id,
        );
    }
}
