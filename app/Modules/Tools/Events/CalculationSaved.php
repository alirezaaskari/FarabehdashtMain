<?php

declare(strict_types=1);

namespace App\Modules\Tools\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Tools\Domain\SavedCalculation;
use App\Support\Audit\AuditEntry;

/**
 * کاربر یک محاسبه را ذخیره کرد.
 *
 * دفتر رویداد عمداً **مقادیر اندازه‌گیری را نمی‌نویسد**: داده میدانی می‌تواند
 * محرمانه کارفرما باشد و دفتر رویداد جای نگه‌داشتنش نیست. شناسه محاسبه،
 * ابزار و نسخه فرمول کافی است؛ خود مقادیر در ردیف محاسبه‌اند.
 */
final readonly class CalculationSaved implements AuditableEvent
{
    public function __construct(public SavedCalculation $calculation) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'tools.calculation_saved',
            subjectType: SavedCalculation::class,
            subjectId: $this->calculation->uuid,
            actorId: $this->calculation->user_id,
            after: [
                'tool' => $this->calculation->tool_slug,
                'formula' => $this->calculation->formula_id,
                'version' => $this->calculation->formula_version,
            ],
        );
    }
}
