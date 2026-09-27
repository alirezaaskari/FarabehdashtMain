<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Actions;

use App\Modules\Jobs\Domain\Enums\ApplicationStatus;
use App\Modules\Jobs\Domain\JobApplication;
use App\Modules\Jobs\Events\ApplicationConsentChanged;
use App\Modules\Jobs\Events\ApplicationStatusChanged;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * اختیار کارجو روی درخواست خودش: دادن یا پس‌گرفتن اجازه شماره و ایمیل، و
 * پس‌گرفتن کل درخواست که فایل رزومه را هم پاک می‌کند.
 */
final readonly class ManageOwnApplication
{
    public function __construct(
        private Factory $storage,
        private Dispatcher $events,
    ) {}

    public function shareContact(int $userId, JobApplication $application, bool $share): void
    {
        $this->authorize($userId, $application);

        if ($application->share_contact === $share) {
            return;
        }

        $application->forceFill(['share_contact' => $share])->save();
        $this->events->dispatch(new ApplicationConsentChanged($application));
    }

    public function withdraw(int $userId, JobApplication $application): void
    {
        $this->authorize($userId, $application);

        if (! $application->status->isOpen()) {
            throw new RuntimeException('درخواستی که کارفرما درباره‌اش تصمیم گرفته پس گرفته نمی‌شود.');
        }

        if ($application->resume_path !== null) {
            $this->storage->disk('local')->delete($application->resume_path);
        }

        $previous = $application->status;
        $application->forceFill([
            'status' => ApplicationStatus::Withdrawn,
            'status_changed_at' => Carbon::now(),
            'share_contact' => false,
            'resume_path' => null,
        ])->save();

        $this->events->dispatch(new ApplicationStatusChanged($application, $previous, $userId));
    }

    private function authorize(int $userId, JobApplication $application): void
    {
        if ($application->user_id !== $userId) {
            throw new RuntimeException('این درخواست مال شما نیست.');
        }

        if ($application->status === ApplicationStatus::Withdrawn) {
            throw new RuntimeException('این درخواست پس گرفته شده است.');
        }
    }
}
