<?php

declare(strict_types=1);

namespace App\Modules\Courses\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Courses\Domain\Course;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/** جلسه‌ها و سؤال‌های تازه یک دوره منتشرشده تأیید شد. */
final readonly class CourseChangesApproved implements AuditableEvent, UserNotifiableEvent
{
    /** @param  array{sessions: int, questions: int}  $counts */
    public function __construct(
        public Course $course,
        public int $actorId,
        public array $counts,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'courses.course_changes_approved',
            subjectType: Course::class,
            subjectId: $this->course->uuid,
            actorId: $this->actorId,
            after: $this->counts,
            context: ['slug' => $this->course->slug, 'instructor_user_id' => $this->course->instructor_user_id],
        );
    }

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->course->instructor_user_id,
            kind: 'courses.course_changes_approved',
            title: sprintf('تغییرات دوره «%s» تأیید شد و به دانشجوها رسید', $this->course->title),
            routeName: 'courses.instructor.courses.edit',
            routeParameters: ['course' => $this->course->getKey()],
        )];
    }
}
