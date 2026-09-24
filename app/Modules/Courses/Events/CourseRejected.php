<?php

declare(strict_types=1);

namespace App\Modules\Courses\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Courses\Domain\Course;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

final readonly class CourseRejected implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(
        public Course $course,
        public int $actorId,
        public string $note,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'courses.course_rejected',
            subjectType: Course::class,
            subjectId: $this->course->uuid,
            actorId: $this->actorId,
            after: ['status' => $this->course->status->value],
            context: [
                'slug' => $this->course->slug,
                'instructor_user_id' => $this->course->instructor_user_id,
                'note' => $this->note,
            ],
        );
    }

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->course->instructor_user_id,
            kind: 'courses.course_rejected',
            title: sprintf('دوره «%s» برای انتشار تأیید نشد', $this->course->title),
            body: $this->note,
            routeName: 'courses.instructor.courses.edit',
            routeParameters: ['course' => $this->course->getKey()],
        )];
    }
}
