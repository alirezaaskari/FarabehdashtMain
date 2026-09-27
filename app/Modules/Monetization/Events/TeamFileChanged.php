<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Monetization\Domain\TeamFile;
use App\Support\Audit\AuditEntry;

/** بارگذاری یا حذف فایل کتابخانه تیم؛ بی نام فایل و بی محتوا. */
final readonly class TeamFileChanged implements AuditableEvent
{
    public const UPLOADED = 'uploaded';

    public const DELETED = 'deleted';

    public function __construct(
        public TeamFile $file,
        public string $step,
        public int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'monetization.team_file_'.$this->step,
            subjectType: TeamFile::class,
            subjectId: $this->file->uuid,
            actorId: $this->actorId,
            context: ['team_id' => $this->file->team_id, 'size_bytes' => $this->file->size_bytes],
        );
    }
}
