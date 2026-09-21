<?php

declare(strict_types=1);

namespace App\Modules\Courses\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Courses\Domain\Course;
use App\Support\Audit\AuditEntry;

final readonly class CourseRejected implements AuditableEvent
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
}
