<?php

declare(strict_types=1);

namespace App\Modules\Expert\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\LinkableContentChanged;
use App\Support\Audit\AuditEntry;

/**
 * پرسش و پاسخ‌های تحریریه با متن کد همگام شد. فقط وقتی چیزی عوض شده منتشر
 * می‌شود؛ پیوندهای داخلی هم از روی متن تازه ساخته می‌شوند.
 */
final readonly class EditorialQuestionsSynced implements AuditableEvent, LinkableContentChanged
{
    public function __construct(
        public int $created,
        public int $updated,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'expert.editorial_synced',
            after: ['created' => $this->created, 'updated' => $this->updated],
        );
    }

    public function affectsPublicLinks(): bool
    {
        return true;
    }
}
