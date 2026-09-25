<?php

declare(strict_types=1);

namespace App\Modules\Core\Listeners;

use App\Contracts\RevisionEvent;
use App\Modules\Core\Services\RevisionRecorder;

/** پل میان رویدادهای محتوا و تاریخچه نسخه‌ها. */
final readonly class RecordRevisionEvent
{
    public function __construct(private RevisionRecorder $recorder) {}

    public function handle(RevisionEvent $event): void
    {
        $this->recorder->record($event->revisable(), $event->revisionReason(), $event->revisionAuthorId());
    }
}
