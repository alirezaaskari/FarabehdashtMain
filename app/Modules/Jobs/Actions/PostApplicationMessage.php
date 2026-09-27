<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Actions;

use App\Modules\Jobs\Domain\ApplicationMessage;
use App\Modules\Jobs\Domain\JobApplication;
use App\Modules\Jobs\Events\ApplicationMessagePosted;
use Illuminate\Contracts\Events\Dispatcher;
use RuntimeException;

/**
 * گفت‌وگوی کارجو و کارفرما درباره یک درخواست؛ تا وقتی درخواست باز است یا
 * کارفرما استخدامش کرده.
 */
final readonly class PostApplicationMessage
{
    public function __construct(private Dispatcher $events) {}

    public function handle(JobApplication $application, int $userId, string $body): ApplicationMessage
    {
        if (! $application->isParty($userId)) {
            throw new RuntimeException('فقط کارجو و کارفرمای همین درخواست پیام می‌فرستند.');
        }

        if (! $application->status->allowsMessages()) {
            throw new RuntimeException('گفت‌وگوی این درخواست بسته است.');
        }

        $message = ApplicationMessage::query()->create([
            'application_id' => $application->id,
            'user_id' => $userId,
            'body' => trim($body),
        ]);

        $message->setRelation('application', $application);
        $this->events->dispatch(new ApplicationMessagePosted($message));

        return $message;
    }
}
