<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Events;

use App\Contracts\UserNotifiableEvent;
use App\Modules\Jobs\Domain\ApplicationMessage;
use App\Support\Notifications\UserNotice;

/**
 * پیام تازه درباره یک درخواست؛ به طرف دیگر خبر می‌دهد. متن پیام در اعلان نمی‌آید.
 */
final readonly class ApplicationMessagePosted implements UserNotifiableEvent
{
    public function __construct(public ApplicationMessage $message) {}

    public function userNotices(): array
    {
        $application = $this->message->application;
        $fromApplicant = $this->message->user_id === $application->user_id;

        return [new UserNotice(
            recipientId: $fromApplicant ? $application->employerId() : $application->user_id,
            kind: 'jobs.application_message',
            title: 'پیام تازه درباره «'.$application->posting->title.'»',
            routeName: $fromApplicant ? 'jobs.employer.applicants.show' : 'jobs.applications.show',
            routeParameters: ['uuid' => $application->uuid],
        )];
    }
}
